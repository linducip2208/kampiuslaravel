<?php

namespace App\Support;

/**
 * Pembangkit QR Code mandiri tanpa dependensi tambahan.
 *
 * Mode byte (UTF-8), level koreksi kesalahan L (~7%), versi 1-6 otomatis
 * mengikuti panjang teks (maksimal 134 byte). Hasil berupa SVG inline
 * sehingga bisa dicetak / disimpan PDF tanpa request eksternal.
 *
 * Implementasi mengikuti ISO/IEC 18004 untuk subset di atas:
 * pola finder + separator, pola timing, pola alignment, modul gelap,
 * penempatan data zigzag, masker 0-7 (dipilih via penalti N1-N3),
 * Reed-Solomon GF(256) primitif 0x11D, dan format info BCH(15,5).
 */
class QrSvg
{
    /**
     * @var array<int, array{size:int, data:int, ec:int, blocks:int, align:list<int>}>
     */
    private const VERSIONS = [
        1 => ['size' => 21, 'data' => 19, 'ec' => 7, 'blocks' => 1, 'align' => []],
        2 => ['size' => 25, 'data' => 34, 'ec' => 10, 'blocks' => 1, 'align' => [6, 18]],
        3 => ['size' => 29, 'data' => 55, 'ec' => 15, 'blocks' => 1, 'align' => [6, 22]],
        4 => ['size' => 33, 'data' => 80, 'ec' => 20, 'blocks' => 1, 'align' => [6, 26]],
        5 => ['size' => 37, 'data' => 108, 'ec' => 26, 'blocks' => 1, 'align' => [6, 30]],
        6 => ['size' => 41, 'data' => 136, 'ec' => 18, 'blocks' => 2, 'align' => [6, 34]],
    ];

    /** Bit format untuk level EC L. */
    private const EC_BITS_L = 0b01;

    /** @var list<int> */
    private array $exp = [];

    /** @var list<int> */
    private array $log = [];

    public function __construct()
    {
        $this->exp = array_fill(0, 512, 0);
        $this->log = array_fill(0, 256, 0);

        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $this->exp[$i] = $x;
            $this->log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $this->exp[$i] = $this->exp[$i - 255];
        }
    }

    /**
     * Bangun matriks modul (1 = gelap, 0 = terang).
     *
     * @return list<list<int>>
     */
    public function matrix(string $text): array
    {
        $version = $this->versionFor($text);
        $spec = self::VERSIONS[$version];
        $size = $spec['size'];

        $codewords = $this->dataCodewords($text, $spec['data']);
        $final = $this->addEccInterleave($codewords, $spec);

        $modules = array_fill(0, $size, array_fill(0, $size, -1));
        $func = array_fill(0, $size, array_fill(0, $size, false));

        $this->drawFunctionPatterns($modules, $func, $size, $spec['align']);

        $bits = [];
        foreach ($final as $cw) {
            for ($i = 7; $i >= 0; $i--) {
                $bits[] = ($cw >> $i) & 1;
            }
        }
        $this->placeData($modules, $func, $size, $bits);

        $mask = $this->bestMask($modules, $func, $size);
        $this->applyMask($modules, $func, $size, $mask);
        $this->drawFormat($modules, $func, $size, $mask);

        return array_map(
            fn (array $row): array => array_map(fn (int $v): int => $v === 1 ? 1 : 0, $row),
            $modules
        );
    }

    /**
     * Render SVG inline siap cetak.
     */
    public static function svg(string $text, int $scale = 4, int $border = 4): string
    {
        $matrix = (new self)->matrix($text);
        $n = count($matrix);
        $dim = ($n + $border * 2) * $scale;

        $path = '';
        foreach ($matrix as $r => $row) {
            foreach ($row as $c => $v) {
                if ($v === 1) {
                    $x = ($c + $border) * $scale;
                    $y = ($r + $border) * $scale;
                    $path .= "M{$x} {$y}h{$scale}v{$scale}h-{$scale}z";
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$dim.' '.$dim.'" width="'.$dim.'" height="'.$dim.'" shape-rendering="crispEdges" role="img" aria-label="QR verifikasi"><rect width="'.$dim.'" height="'.$dim.'" fill="#fff"/><path d="'.$path.'" fill="#000"/></svg>';
    }

    /**
     * Varian aman untuk view: null bila teks melebihi kapasitas versi 6.
     */
    public static function trySvg(string $text, int $scale = 4, int $border = 4): ?string
    {
        try {
            return self::svg($text, $scale, $border);
        } catch (\RuntimeException) {
            return null;
        }
    }

    /**
     * Versi minimal yang muat (1-6).
     */
    public function versionFor(string $text): int
    {
        $len = strlen($text);

        foreach (self::VERSIONS as $version => $spec) {
            $capacityBits = $spec['data'] * 8;
            $needed = 4 + 8 + $len * 8;
            if ($needed > $capacityBits) {
                continue;
            }

            // Terminator + padding selalu bisa dipenuhi bila muat.
            return $version;
        }

        throw new \RuntimeException('Teks melebihi kapasitas QR versi 6 level L (maks 134 byte).');
    }

    /**
     * @return list<int> data codewords (sudah terminator + padding 0xEC/0x11)
     */
    private function dataCodewords(string $text, int $dataCapacity): array
    {
        $bits = '0100'.str_pad(decbin(strlen($text)), 8, '0', STR_PAD_LEFT);
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $bits .= str_pad(decbin(ord($text[$i])), 8, '0', STR_PAD_LEFT);
        }

        $capacityBits = $dataCapacity * 8;
        $bits .= substr('0000', 0, min(4, $capacityBits - strlen($bits)));
        while (strlen($bits) % 8 !== 0) {
            $bits .= '0';
        }

        $codewords = [];
        for ($i = 0; $i < strlen($bits); $i += 8) {
            $codewords[] = bindec(substr($bits, $i, 8));
        }

        $pad = [0xEC, 0x11];
        $k = 0;
        while (count($codewords) < $dataCapacity) {
            $codewords[] = $pad[$k % 2];
            $k++;
        }

        return $codewords;
    }

    /**
     * @param  array{size:int, data:int, ec:int, blocks:int, align:list<int>}  $spec
     * @param  list<int>  $codewords
     * @return list<int>
     */
    private function addEccInterleave(array $codewords, array $spec): array
    {
        $numBlocks = $spec['blocks'];
        $ecLen = $spec['ec'];
        $perBlock = intdiv($spec['data'], $numBlocks);

        $blocks = array_chunk($codewords, $perBlock);
        $eccBlocks = [];
        foreach ($blocks as $block) {
            $eccBlocks[] = $this->rsRemainder($block, $ecLen);
        }

        $final = [];
        for ($i = 0; $i < $perBlock; $i++) {
            foreach ($blocks as $block) {
                if (isset($block[$i])) {
                    $final[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecLen; $i++) {
            foreach ($eccBlocks as $ecc) {
                $final[] = $ecc[$i];
            }
        }

        return $final;
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    private function rsRemainder(array $data, int $degree): array
    {
        $divisor = [1];
        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($divisor) + 1, 0);
            $factor = $this->exp[$i];
            foreach ($divisor as $j => $coef) {
                $next[$j] ^= $this->gfMul($coef, $factor);
                $next[$j + 1] ^= $coef;
            }
            $divisor = $next;
        }
        // $divisor tersusun ascending (indeks = pangkat x); ubah ke descending
        // tanpa koefisien depan 1 untuk pembagian sintetis.
        array_pop($divisor);
        $divisor = array_reverse($divisor);

        $result = array_merge($data, array_fill(0, $degree, 0));
        for ($i = 0; $i < count($data); $i++) {
            $coef = $result[$i];
            if ($coef !== 0) {
                for ($j = 0; $j < $degree; $j++) {
                    $result[$i + 1 + $j] ^= $this->gfMul($divisor[$j], $coef);
                }
            }
        }

        return array_slice($result, count($data));
    }

    private function gfMul(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return $this->exp[$this->log[$a] + $this->log[$b]];
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     * @param  list<int>  $align
     */
    private function drawFunctionPatterns(array &$modules, array &$func, int $size, array $align): void
    {
        $this->drawFinder($modules, $func, $size, 0, 0);
        $this->drawFinder($modules, $func, $size, $size - 7, 0);
        $this->drawFinder($modules, $func, $size, 0, $size - 7);

        for ($i = 8; $i < $size - 8; $i++) {
            $v = $i % 2 === 0 ? 1 : 0;
            $this->setFunc($modules, $func, 6, $i, $v);
            $this->setFunc($modules, $func, $i, 6, $v);
        }

        $n = count($align);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $n - 1) || ($i === $n - 1 && $j === 0)) {
                    continue;
                }
                $this->drawAlignment($modules, $func, $align[$j], $align[$i]);
            }
        }

        // Cadangkan area format info agar tidak terisi data.
        for ($i = 0; $i <= 8; $i++) {
            if ($i !== 6) {
                $func[8][$i] = true;
                $func[$i][8] = true;
            }
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $func[8][$i] = true;
            $func[$i][8] = true;
        }
        $func[$size - 8][8] = true;
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function drawFinder(array &$modules, array &$func, int $size, int $cx, int $cy): void
    {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $x = $cx + $dx;
                $y = $cy + $dy;
                if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
                    continue;
                }
                $in = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
                $dark = $in && ($dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 || ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4));
                $this->setFunc($modules, $func, $x, $y, $in ? (int) $dark : 0);
            }
        }
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function drawAlignment(array &$modules, array &$func, int $cx, int $cy): void
    {
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $dark = max(abs($dx), abs($dy)) !== 1;
                $this->setFunc($modules, $func, $cx + $dx, $cy + $dy, (int) $dark);
            }
        }
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function setFunc(array &$modules, array &$func, int $x, int $y, int $value): void
    {
        $modules[$y][$x] = $value;
        $func[$y][$x] = true;
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     * @param  list<int>  $bits
     */
    private function placeData(array &$modules, array &$func, int $size, array $bits): void
    {
        $i = 0;
        $total = count($bits);
        $upward = true;
        for ($right = $size - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $x = $right - $j;
                    $y = $upward ? $size - 1 - $vert : $vert;
                    if (! $func[$y][$x] && $i < $total) {
                        $modules[$y][$x] = $bits[$i++];
                    }
                }
            }
            $upward = ! $upward;
        }
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function applyMask(array &$modules, array &$func, int $size, int $mask): void
    {
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                if (! $func[$r][$c] && $this->maskBit($mask, $r, $c)) {
                    $modules[$r][$c] ^= 1;
                }
            }
        }
    }

    private function maskBit(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
            5 => (($r * $c) % 2 + ($r * $c) % 3) === 0,
            6 => ((($r * $c) % 2) + (($r * $c) % 3)) % 2 === 0,
            default => ((($r + $c) % 2) + (($r * $c) % 3)) % 2 === 0,
        };
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function bestMask(array $modules, array $func, int $size): int
    {
        $best = 0;
        $bestScore = PHP_INT_MAX;

        for ($mask = 0; $mask < 8; $mask++) {
            $trial = $modules;
            $this->applyMask($trial, $func, $size, $mask);
            $score = $this->penalty($trial, $size);
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $mask;
            }
        }

        return $best;
    }

    /**
     * Penalti N1 (run), N2 (blok 2x2), N3 (pola mirip finder).
     *
     * @param  list<list<int>>  $m
     */
    private function penalty(array $m, int $size): int
    {
        $score = 0;

        for ($r = 0; $r < $size; $r++) {
            $run = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($m[$r][$c] === $m[$r][$c - 1]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $score += 3 + ($run - 5);
                    }
                    $run = 1;
                }
            }
            if ($run >= 5) {
                $score += 3 + ($run - 5);
            }
        }

        for ($c = 0; $c < $size; $c++) {
            $run = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($m[$r][$c] === $m[$r - 1][$c]) {
                    $run++;
                } else {
                    if ($run >= 5) {
                        $score += 3 + ($run - 5);
                    }
                    $run = 1;
                }
            }
            if ($run >= 5) {
                $score += 3 + ($run - 5);
            }
        }

        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $v = $m[$r][$c];
                if ($v === $m[$r][$c + 1] && $v === $m[$r + 1][$c] && $v === $m[$r + 1][$c + 1]) {
                    $score += 3;
                }
            }
        }

        $finderLike = [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0];
        $finderLikeInv = [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1];
        $lines = [];
        for ($r = 0; $r < $size; $r++) {
            $lines[] = $m[$r];
        }
        for ($c = 0; $c < $size; $c++) {
            $lines[] = array_column($m, $c);
        }
        foreach ($lines as $line) {
            for ($i = 0; $i + 11 <= $size; $i++) {
                $seg = array_slice($line, $i, 11);
                if ($seg === $finderLike || $seg === $finderLikeInv) {
                    $score += 40;
                }
            }
        }

        return $score;
    }

    /**
     * @param  list<list<int>>  $modules
     * @param  list<list<bool>>  $func
     */
    private function drawFormat(array &$modules, array &$func, int $size, int $mask): void
    {
        $data = (self::EC_BITS_L << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        $bit = fn (int $i): int => ($bits >> $i) & 1;

        for ($i = 0; $i <= 5; $i++) {
            $this->setFunc($modules, $func, $i, 8, $bit($i));
        }
        $this->setFunc($modules, $func, 7, 8, $bit(6));
        $this->setFunc($modules, $func, 8, 8, $bit(7));
        $this->setFunc($modules, $func, 8, 7, $bit(8));
        for ($i = 9; $i < 15; $i++) {
            $this->setFunc($modules, $func, 8, 14 - $i, $bit($i));
        }

        // Salinan kedua: vertikal atas-ke-bawah bit 8..14, horizontal
        // kanan-ke-kiri bit 0..7 (urutan ini yang diterima decoder umum).
        for ($i = 8; $i < 15; $i++) {
            $this->setFunc($modules, $func, 8, $size - 7 + ($i - 8), $bit($i));
        }
        for ($i = 0; $i < 8; $i++) {
            $this->setFunc($modules, $func, $size - 1 - $i, 8, $bit($i));
        }
        $this->setFunc($modules, $func, 8, $size - 8, 1);
    }
}

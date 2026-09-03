@props(['values' => [], 'stroke' => 'currentColor', 'fill' => 'currentColor', 'height' => 40, 'width' => 120])

@php
    $points = array_values(array_map('floatval', $values));
    $count = count($points);
    $max = $count ? max($points) : 0;
    $min = $count ? min($points) : 0;
    $range = ($max - $min) ?: 1;
    $stepX = $count > 1 ? $width / ($count - 1) : 0;
    // A small vertical inset keeps the line from ever touching the very
    // top/bottom edge, so it doesn't look clipped inside its box.
    $inset = $height * 0.12;
    $drawHeight = $height - ($inset * 2);

    $coords = [];
    foreach ($points as $i => $v) {
        $x = round($i * $stepX, 2);
        $y = round($inset + $drawHeight - (($v - $min) / $range) * $drawHeight, 2);
        $coords[] = "{$x},{$y}";
    }
    $polylinePoints = implode(' ', $coords);
    $areaPath = $count > 1 ? "M0,{$height} L{$polylinePoints} L{$width},{$height} Z" : null;
@endphp

@if ($count > 1)
    <svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" {{ $attributes->class(['block h-full w-full overflow-visible']) }}>
        <path d="{{ $areaPath }}" fill="{{ $fill }}" opacity="0.16" />
        <polyline points="{{ $polylinePoints }}" fill="none" stroke="{{ $stroke }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
@endif

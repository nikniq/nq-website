{{-- Single-series daily column chart. Needs: $rows (daily rows), $field, $title, $unit. --}}
@php($max = max(1, (int) $rows->max($field)))
@php($total = $rows->sum($field))
<figure class="chart" aria-labelledby="{{ $field }}-h">
    <figcaption class="chart-head">
        <h3 id="{{ $field }}-h">{{ $title }}</h3>
        <span class="chart-total">@if($peakOnly ?? false)busiest day {{ number_format($max) }}@else{{ number_format($total) }} {{ $total === 1 ? \Illuminate\Support\Str::singular($unit) : $unit }} in {{ $rows->count() }} days @endif</span>
    </figcaption>
    <div class="chart-plot" role="img" aria-label="{{ $title }}: @unless($peakOnly ?? false){{ number_format($total) }} {{ $unit }} in total, @endunless peak {{ number_format($max) }} in a day. A table follows.">
        <span class="chart-y top">{{ number_format($max) }}</span>
        @if($max >= 2 && $max % 2 === 0)<span class="chart-y mid">{{ number_format($max / 2) }}</span>@endif
        <div class="chart-grid" aria-hidden="true"><i></i><i></i></div>
        <div class="chart-bars" style="--n:{{ $rows->count() }}">
            @foreach($rows as $r)
                @php($v = (int) $r->{$field})
                <span class="col" tabindex="0" data-tip="{{ $r->date->format('D j M') }}: {{ number_format($v) }} {{ $v === 1 ? \Illuminate\Support\Str::singular($unit) : $unit }}">
                    <span class="bar" style="height:{{ $v ? max(2, round($v / $max * 100, 2)) : 0 }}%"></span>
                </span>
            @endforeach
        </div>
    </div>
    <div class="chart-x" aria-hidden="true">
        <span>{{ $rows->first()->date->format('j M') }}</span>
        <span>{{ $rows->get(intdiv($rows->count(), 2))->date->format('j M') }}</span>
        <span>{{ $rows->last()->date->format('j M') }}</span>
    </div>
    <details class="chart-table">
        <summary>Show as table</summary>
        <div class="table-scroll"><table>
            <thead><tr><th>Date</th><th class="num">{{ ucfirst($unit) }}</th></tr></thead>
            <tbody>@foreach($rows->reverse() as $r)<tr><td>{{ $r->date->format('D j M Y') }}</td><td class="num">{{ number_format($r->{$field}) }}</td></tr>@endforeach</tbody>
        </table></div>
    </details>
</figure>

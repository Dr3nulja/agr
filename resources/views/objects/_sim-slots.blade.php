{{--
    Выбор SIM для модемов объекта из регистра (вместо ручного ввода GSMNR/PIN/PUK).
    $simOptions — карты, которые можно поставить (свободные, выданные, уже на этом объекте)
    $simSlots   — [слот => id карты] для текущего объекта
--}}
@foreach(\App\Models\SimCard::SLOTS as $slot => $slotLabel)
    <div class="field">
        <label>{{ $slotLabel }} {{ $slot === 1 ? '(GSM Number)' : '(GSM Number 2)' }}</label>
        <select name="sim{{ $slot }}_id">
            <option value="">— SIM puudub —</option>
            @foreach($simOptions as $sim)
                <option value="{{ $sim->id }}" @selected((string) old('sim' . $slot . '_id', $simSlots[$slot] ?? '') === (string) $sim->id)>
                    {{ $sim->label() }}
                    @if($sim->status === \App\Models\SimCard::STATUS_ISSUED)
                        — väljastatud: {{ $sim->issued_to }}
                    @elseif($sim->status === \App\Models\SimCard::STATUS_FREE)
                        — vaba
                    @endif
                </option>
            @endforeach
        </select>
    </div>
@endforeach
<p class="span-2" style="color: var(--text-muted); font-size: 0.9rem; margin: -6px 0 0;">
    SIM-id, PIN- ja PUK-koodid on <a href="{{ route('sims.index') }}" style="color: var(--primary);">SIM-registris</a>. Vahetamisel läheb vana SIM vabaks.
</p>

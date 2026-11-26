@foreach($objects as $object)
    <tr class="{{ ($object->status == 2 ? 'inactive-row' : '') }} {{ $object->selDate ? 'selected-row' : ($object->water_checked_at ? 'water-row' : '') }}">
        <td class="address-cell">{{ $object->address }}</td>
        <td class="city-cell">{{ $object->City }}</td>
        <td class="description-cell">{!! nl2br(e($object->Description ?? '-')) !!}</td>
        <td class="status-cell">
            @if($object->status == 1)
                <span class="status-pill is-active">✓ Aktiivne</span>
            @elseif($object->status == 2)
                <span class="status-pill is-inactive">✕ Mitteaktiivne</span>
            @else
                <span class="status-pill is-inactive">Teadmata</span>
            @endif
        </td>
        <td class="company-cell">{{ \App\Models\AgrObject::companyDisplayName($object->Company) }}</td>
        <td class="system-cell">
            @if($object->dtype == 1) 💧 Apator
            @elseif($object->dtype == 2) 🔥 Siemens
            @elseif($object->dtype == 3) ⚡ Elekter
            @elseif($object->dtype == 4) 📡 LoRa
            @else {{ $object->dtype }} @endif
        </td>
        <td class="checked-cell">
            @if($object->selDate)
                <span class="status-pill is-checked">✓ Esitatud</span>
            @elseif($object->water_checked_at)
                <span class="status-pill is-water">💧 Vesi</span>
            @else
                <span class="status-pill is-unchecked">Esitamata</span>
            @endif
        </td>
        <td class="devices-cell">{{ $object->Devqtty ?? 0 }}</td>
        <td class="errors-cell">
            <span class="error-icon water" title="Vesi">{{ $object->water_errors ?? 0 }}</span>
            <span class="error-icon warning" title="Soojus">{{ $object->heat_errors ?? 0 }}</span>
            <span class="error-icon danger" title="Vead">{{ $object->errors ?? 0 }}</span>
        </td>
        <td class="fw-cell">{{ $object->ver ?? '-' }}</td>
        <td class="actions-cell">
            <div class="row-actions">
                <a href="{{ route('objects.show', $object->id) }}" class="view-btn">👁️ Vaata</a>
                @if(session('user')->role == 1)
                <div class="action-menu">
                    <button class="menu-btn" onclick="toggleMenu(this)">⋯</button>
                    <div class="menu-dropdown">
                        @if(! $object->selDate)
                            {{-- Следующий чек: вода → тепло (или сразу «сдано всё», если в доме что-то одно) --}}
                            <form method="post" action="{{ route('objects.check', $object->id) }}" style="margin: 0; border-bottom: 1px solid var(--border);" onsubmit="return confirm('{{ $object->water_checked_at ? 'Märkida soojus esitatuks?' : 'Märkida järgmine samm (vesi / kõik) esitatuks?' }}');">
                                @csrf
                                <input type="hidden" name="from" value="list">
                                <button type="submit" class="menu-item" style="color: oklch(0.45 0.13 155);">
                                    ✓ {{ $object->water_checked_at ? 'Märgi soojus esitatuks' : 'Märgi esitatuks' }}
                                </button>
                            </form>
                        @endif
                        @if($object->selDate || $object->water_checked_at)
                            <form method="post" action="{{ route('objects.uncheck', $object->id) }}" style="margin: 0; border-bottom: 1px solid var(--border);" onsubmit="return confirm('Eemaldada märked?');">
                                @csrf
                                <input type="hidden" name="from" value="list">
                                <button type="submit" class="menu-item">↩ Eemalda märked</button>
                            </form>
                        @endif
                        <a href="{{ route('objects.edit', $object->id) }}" class="menu-item">
                            ✏️ Muuda
                        </a>
                        <a href="{{ route('objects.soe', $object->id) }}" class="menu-item">
                            ⚙️ SOE
                        </a>
                        <a href="{{ route('readings.index', $object->id) }}" class="menu-item">
                            📈 Näidud
                        </a>
                        <a href="{{ route('objects.export.current', $object->id) }}" class="menu-item">
                            📥 Export Current Val XLSX
                        </a>
                        <a href="{{ route('objects.export.month_start', $object->id) }}" class="menu-item">
                            📥 Export Month Start
                        </a>
                        <a href="{{ route('objects.export.alokator', $object->id) }}" class="menu-item">
                            📥 Export Alokator XLSX
                        </a>
                        <a href="{{ route('objects.export.korto', $object->id) }}" class="menu-item">
                            📥 Export Korto CSV
                        </a>
                        <button class="menu-item delete" type="button" onclick="if(confirm('Kas kustutada objekt?')) { deleteObject({{ $object->id }}) }">
                            🗑️ Kustuta
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </td>
    </tr>
@endforeach

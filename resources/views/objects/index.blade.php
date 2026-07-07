@extends('layouts.app')

@section('title', 'Objects - AGR')

@section('extra-styles')
    <style>
        .filter-section {
            background: var(--surface);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            margin-bottom: 16px;
        }

        .filter-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-chips {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            background: var(--primary);
            color: white;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .chip:hover {
            background: var(--primary-light);
        }

        .chip.inactive {
            background: #94a3b8;
        }

        .chip-close {
            font-weight: bold;
            margin-left: 4px;
        }

        .objects-table {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
            overflow: visible;
        }

        .table-responsive {
            overflow-x: auto;
            overflow-y: visible;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg);
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-muted);
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: all 0.2s;
        }

        tbody tr:hover {
            background: var(--bg);
        }

        tbody tr.inactive-row {
            background: #fef3c7;
        }

        tbody tr.inactive-row:hover {
            background: #fde68a;
        }

        tbody tr.selected-row {
            background: #d1fae5;
        }

        tbody tr.selected-row.inactive-row {
            background: #fcd38c;
        }

        td {
            padding: 12px 14px;
            font-size: 0.95rem;
        }

        .address-cell {
            font-weight: 600;
            color: var(--text);
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .city-cell {
            color: var(--text-muted);
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .description-cell {
            color: var(--text-muted);
            font-size: 0.9rem;
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .errors-cell {
            font-weight: 600;
            text-align: center;
        }

        .error-icon {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-right: 4px;
        }

        .error-icon.water {
            background: #dbeafe;
            color: #0369a1;
        }

        .error-icon.warning {
            background: #fed7aa;
            color: #ea580c;
        }

        .error-icon.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .fw-cell {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .action-menu {
            position: relative;
            display: inline-block;
        }

        .menu-btn {
            background: var(--bg);
            border: 1px solid var(--border);
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
            color: var(--text-muted);
        }

        .menu-btn:hover {
            background: var(--border);
            color: var(--text);
        }

        .menu-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
            min-width: 200px;
            z-index: 9999;
            display: none;
            margin-top: 4px;
        }

        .menu-dropdown.above {
            top: auto;
            bottom: 100%;
            margin-top: 0;
            margin-bottom: 4px;
        }

        .menu-dropdown.active {
            display: block;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
            transition: all 0.2s;
            border-bottom: 1px solid var(--border);
        }

        .menu-item:last-child {
            border-bottom: none;
        }

        .menu-item:hover {
            background: var(--bg);
            color: var(--primary);
        }

        .menu-item.delete:hover {
            background: #fee2e2;
            color: var(--danger);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .pagination-section {
            padding: 16px;
            text-align: center;
            border-top: 1px solid var(--border);
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-section h2 {
            margin: 0;
        }

        .create-btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 0;
            background: var(--primary);
            color: white;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .create-btn:hover {
            background: var(--primary-light);
        }
    </style>
@endsection

@section('content')
    <div class="header-section">
        <div>
            <h2>🏠 Objects Management</h2>
            <p style="color: var(--text-muted); margin-top: 4px;">Showing {{ $objects->count() }} of {{ $objects->total() }} objects</p>
        </div>
        @if(session('user')->role == 1)
            <a href="{{ route('objects.create') }}" class="create-btn">+ Create Object</a>
        @endif
    </div>
    <div class="filter-section" style="margin-bottom: 8px;">
        <div class="filter-group" style="justify-content: space-between; gap: 16px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <div class="chip" style="background: #2563eb;">Total {{ $summary['total'] }}</div>
                <div class="chip" style="background: #16a34a;">Active {{ $summary['active'] }}</div>
                <div class="chip inactive" style="background: #f97316;">Inactive {{ $summary['inactive'] }}</div>
                <div class="chip" style="background: #0ea5e9;">Checked {{ $summary['checked'] }}</div>
                <div class="chip inactive" style="background: #64748b;">Unchecked {{ $summary['unchecked'] }}</div>
            </div>
        </div>
    </div>

    <div class="filter-section">
        <div class="filter-group">
            <form method="get" action="{{ route('objects.index') }}" class="filter-chips" id="filterForm">
                <input type="text" name="search" class="filter-input" placeholder="Search address, city..." value="{{ request('search') }}" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;">

                <select name="company" class="filter-input" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;">
                    <option value="">All Installers</option>
                    @foreach($companyOptions as $company)
                        <option value="{{ $company }}" {{ request('company') == $company ? 'selected' : '' }}>Installer #{{ $company }}</option>
                    @endforeach
                </select>

                <select name="status" class="filter-input" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;">
                    <option value="">All Status</option>
                    <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active Only</option>
                    <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Inactive Only</option>
                </select>

                <select name="dtype" class="filter-input" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;">
                    <option value="">All Systems</option>
                    <option value="1" {{ request('dtype') == 1 ? 'selected' : '' }}>💧 Water</option>
                    <option value="2" {{ request('dtype') == 2 ? 'selected' : '' }}>🔥 Gas</option>
                    <option value="3" {{ request('dtype') == 3 ? 'selected' : '' }}>⚡ Electric</option>
                </select>

                <select name="checked" class="filter-input" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.9rem;">
                    <option value="">Any Check</option>
                    <option value="checked" {{ request('checked') === 'checked' ? 'selected' : '' }}>Checked</option>
                    <option value="unchecked" {{ request('checked') === 'unchecked' ? 'selected' : '' }}>Unchecked</option>
                </select>

                <button type="submit" class="chip">🔍 Apply</button>
            </form>

            @if(request()->anyFilled(['search', 'status', 'dtype', 'company', 'checked']))
                <a href="{{ route('objects.index') }}" class="chip" style="background: #94a3b8;">✕ Clear Filters</a>
            @endif
        </div>
    </div>

    @if($objects->count() > 0)
        <div class="objects-table">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>City</th>
                            <th>Description</th>
                            <th>Status</th>
                        <th>Installer</th>
                        <th>Type</th>
                        <th>Checked</th>
                        <th>Devices</th>
                        <th style="text-align: center;">Errors</th>
                        <th>FW</th>
                        <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($objects as $object)
                            <tr class="{{ ($object->status == 2 ? 'inactive-row' : '') }} {{ $object->selDate ? 'selected-row' : '' }}">
                                <td class="address-cell">{{ $object->address }}</td>
                                <td class="city-cell">{{ $object->City }}</td>
                                <td class="description-cell">{{ $object->Description ?? '-' }}</td>
                                <td>
                                    @if($object->status == 1)
                                        <span class="chip" style="padding: 4px 10px; font-size: 0.85rem; background: #d1fae5;">✓ Active</span>
                                    @elseif($object->status == 2)
                                        <span class="chip inactive" style="padding: 4px 10px; font-size: 0.85rem;">✕ Inactive</span>
                                    @else
                                        <span class="chip inactive" style="padding: 4px 10px; font-size: 0.85rem;">Unknown</span>
                                    @endif
                                </td>
                                <td>{{ $object->Company ?? '-' }}</td>
                                <td>
                                    @if($object->dtype == 1) 💧 Water
                                    @elseif($object->dtype == 2) 🔥 Gas
                                    @elseif($object->dtype == 3) ⚡ Electric
                                    @else {{ $object->dtype }} @endif
                                </td>
                                <td>
                                    @if($object->selDate)
                                        <span class="chip" style="padding: 4px 10px; font-size: 0.85rem; background: #d1fae5;">Checked</span>
                                    @else
                                        <span class="chip inactive" style="padding: 4px 10px; font-size: 0.85rem;">Unchecked</span>
                                    @endif
                                </td>
                                <td>{{ $object->Devqtty ?? 0 }}</td>
                                <td class="errors-cell">
                                    <span class="error-icon water" title="Water">{{ $object->water_errors ?? 0 }}</span>
                                    <span class="error-icon warning" title="Heat">{{ $object->heat_errors ?? 0 }}</span>
                                    <span class="error-icon danger" title="Errors">{{ $object->errors ?? 0 }}</span>
                                </td>
                                <td class="fw-cell">{{ $object->ver ?? '-' }}</td>
                                <td>
                                    <div class="action-menu">
                                        <button class="menu-btn" onclick="toggleMenu(this)">⋯</button>
                                        <div class="menu-dropdown">
                                            <a href="{{ route('objects.show', $object->id) }}" class="menu-item">
                                                👁️ View
                                            </a>
                                            @if(session('user')->role == 1)
                                                <a href="{{ route('objects.edit', $object->id) }}" class="menu-item">
                                                    ✏️ Edit
                                                </a>
                                                <a href="{{ route('objects.soe', $object->id) }}" class="menu-item">
                                                    ⚙️ SOE
                                                </a>
                                                <button class="menu-item" type="button" onclick="if(confirm('Export as CSV?')) { /* export logic */ }">
                                                    📥 Export CSV
                                                </button>
                                                <form method="post" action="{{ route('objects.check', $object->id) }}" style="margin:0;">
                                                    @csrf
                                                    <button type="submit" class="menu-item" onclick="return confirm('Mark as checked?')">
                                                        ✓ Check
                                                    </button>
                                                </form>
                                                <button class="menu-item delete" type="button" onclick="if(confirm('Delete this object?')) { deleteObject({{ $object->id }}) }">
                                                    🗑️ Delete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($objects->hasPages())
                <div class="pagination-section">
                    {{ $objects->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No objects found</h3>
            <p>Try adjusting your filters or create a new object</p>
        </div>
    @endif

    <script>
        function toggleMenu(btn) {
            const menu = btn.nextElementSibling;
            const wasActive = menu.classList.contains('active');

            document.querySelectorAll('.menu-dropdown.active').forEach(m => {
                m.classList.remove('active');
                m.classList.remove('above');
            });

            if (!wasActive) {
                menu.classList.add('active');
                const buttonRect = btn.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const availableBelow = window.innerHeight - buttonRect.bottom;
                const availableAbove = buttonRect.top;

                if (availableBelow < menuRect.height && availableAbove > menuRect.height) {
                    menu.classList.add('above');
                }
            }
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.action-menu')) {
                document.querySelectorAll('.menu-dropdown.active').forEach(m => {
                    m.classList.remove('active');
                });
            }
        });

        function deleteObject(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/objects/${id}`;
            form.innerHTML = `@csrf @method('DELETE')`;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
@endsection
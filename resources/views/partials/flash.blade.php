{{-- Сообщения после действий: success / error и ошибки валидации --}}
@if(session('success'))
    <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: oklch(0.96 0.05 155); color: oklch(0.38 0.1 155); border: 1px solid oklch(0.88 0.08 155);">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #fef2f2; color: var(--danger); border: 1px solid #fee2e2;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 12px; background: #fef2f2; color: var(--danger); border: 1px solid #fee2e2;">{{ $errors->first() }}</div>
@endif

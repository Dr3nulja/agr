<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload device list</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f5f7fb; color: #0f172a; }
        .page { max-width: 900px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 12px 36px rgba(15, 23, 42, 0.08); }
        .meta { color: #64748b; }
        .field { margin-top: 18px; display: grid; gap: 8px; }
        input[type="file"] { padding: 12px; border: 1px solid #dbe4ea; border-radius: 14px; background: #fff; }
        .actions { margin-top: 20px; display: flex; gap: 12px; }
        .btn { border: 0; border-radius: 14px; padding: 12px 16px; background: #0f766e; color: #fff; font: inherit; font-weight: 700; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <div class="page">
        <div class="panel">
            <h1>Upload device list</h1>
            <p class="meta">{{ $object['address'] }} / {{ $object['city'] }}</p>

            <form method="post" action="{{ route('objects.devices.upload.store', $object['id']) }}" enctype="multipart/form-data">
                @csrf
                <div class="field">
                    <label for="fileToUpload">Choose semicolon-separated file</label>
                    <input type="file" id="fileToUpload" name="fileToUpload" required>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Upload</button>
                    <a class="btn" href="{{ route('objects.show', $object['id']) }}" style="background:#475569;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
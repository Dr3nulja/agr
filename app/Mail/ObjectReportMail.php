<?php

namespace App\Mail;

use App\Models\AgrObject;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Отчёт с показаниями дома (xlsx во вложении)
 */
class ObjectReportMail extends Mailable
{
    public function __construct(
        public AgrObject $object,
        public string $reportTitle,
        public string $filePath,
        public string $fileName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('%s — %s, %s', $this->reportTitle, $this->object->address, $this->object->City),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.object-report');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)
                ->as($this->fileName)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}

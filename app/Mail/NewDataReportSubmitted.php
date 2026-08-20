<?php

namespace App\Mail;

use App\Models\DataReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDataReportSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DataReport $dataReport)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan Data Baru Menunggu Tinjauan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-data-report-submitted',
            with: [
                'dataReport' => $this->dataReport,
            ],
        );
    }
}

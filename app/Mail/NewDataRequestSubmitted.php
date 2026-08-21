<?php

namespace App\Mail;

use App\Models\DataRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewDataRequestSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DataRequest $dataRequest) {}

    public function build()
    {
        return $this->subject('Usulan Data Baru: ' . $this->dataRequest->nama_data)
            ->view('emails.data-requests.new-submission')
            ->with([
                'dataRequest' => $this->dataRequest,
                'reviewUrl'   => route('admin.data_requests.show', $this->dataRequest),
            ]);
    }
}
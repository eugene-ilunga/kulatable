<?php

namespace Modules\Hotel\Livewire;

use Modules\Hotel\Entities\Stay;
use Modules\Hotel\Entities\Folio;
use Modules\Hotel\Entities\FolioLine;
use Modules\Hotel\Enums\FolioLineType;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class FolioView extends Component
{
    use LivewireAlert;

    public $stay;
    public $folio;
    public $showAddChargeModal = false;
    public $showPaymentModal = false;
    public $chargeType = FolioLineType::OTHER->value;
    public $chargeDescription = '';
    public $chargeAmount = 0;
    public $paymentMethod = 'cash';
    public $paymentAmount = 0;
    public $transactionReference = '';

    public function mount($stayId)
    {
        $this->stay = Stay::with(['room.roomType', 'folio.folioLines', 'folio.folioPayments', 'stayGuests.guest'])
            ->findOrFail($stayId);
        
        $this->folio = $this->stay->folio;
        
        if ($this->folio) {
            $this->folio->recalculateTotals();
        }
    }

    public function addCharge()
    {
        $this->validate([
            'chargeType' => 'required|in:' . implode(',', array_column(FolioLineType::cases(), 'value')),
            'chargeDescription' => 'required|string|max:255',
            'chargeAmount' => 'required|numeric|min:0',
        ]);

        FolioLine::create([
            'folio_id' => $this->folio->id,
            'type' => $this->chargeType,
            'description' => $this->chargeDescription,
            'amount' => $this->chargeAmount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'net_amount' => $this->chargeAmount,
            'posting_date' => now(),
            'posted_by' => auth()->id(),
        ]);

        $this->refreshFolioAndTotals();
        $this->reset(['chargeType', 'chargeDescription', 'chargeAmount']);
        $this->showAddChargeModal = false;

        $this->alert('success', __('hotel::modules.folio.chargeAddedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    public function addPayment()
    {
        $this->validate([
            'paymentMethod' => 'required|string',
            'paymentAmount' => 'required|numeric|min:0',
        ]);

        \Modules\Hotel\Entities\FolioPayment::create([
            'folio_id' => $this->folio->id,
            'payment_method' => $this->paymentMethod,
            'amount' => $this->paymentAmount,
            'transaction_reference' => $this->transactionReference,
            'received_by' => auth()->id(),
        ]);

        $this->refreshFolioAndTotals();
        $this->reset(['paymentMethod', 'paymentAmount', 'transactionReference']);
        $this->showPaymentModal = false;

        $this->alert('success', __('hotel::modules.folio.paymentRecordedSuccessfully'), [
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    /**
     * Refresh stay/folio from DB and recalculate folio totals so balance and charges are correct.
     */
    protected function refreshFolioAndTotals(): void
    {
        if (!$this->stay) {
            return;
        }
        $this->stay->refresh();
        $this->stay->load(['room.roomType', 'folio.folioLines', 'folio.folioPayments', 'stayGuests.guest']);
        $this->folio = $this->stay->folio;
        if ($this->folio) {
            $this->folio->recalculateTotals();
            $this->folio->refresh();
        }
    }

    public function render()
    {
        if ($this->folio) {
            $this->folio->recalculateTotals();
            $this->folio->refresh();
            $this->folio->load(['folioLines', 'folioPayments']);
        }

        return view('hotel::livewire.folio-view', [
            'chargeTypes' => FolioLineType::cases(),
        ]);
    }
}

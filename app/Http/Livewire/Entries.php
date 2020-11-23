<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Entry;
use Livewire\WithPagination;

class Entries extends Component
{
    use WithPagination;

    public $document_id, $account_id, $debit, $credit, $at_id;
    public $isOpen = 0;

    public function render()
    {
        return view('livewire.sa.entries',['entries'=>Entry::where('company_id',session('company_id'))->paginate(10)]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    private function resetInputFields(){
        $this->document_id = '';
        $this->account_id = '';
        $this->debit = '';
        $this->credit='';
        $this->at_id = '';
    }

    public function store()
    {
        $this->validate([
            'document_id' => 'required',
            'account_id' => 'required',
            'debit' => 'required',
            'credit' => 'required',
        ]);

        Entry::updateOrCreate(['id' => $this->at_id], [
            'document_id' => $this->document_id,
            'account_id' => $this->account_id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'company_id' => session('company_id'),
        ]);

        session()->flash('message', 
            $this->at_id ? 'Record Updated Successfully.' : 'Record Created Successfully.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $entry = Entry::where('id',$id)->where('company_id',session('company_id'))->first();
        $this->at_id = $id;
        $this->document_id = $entry->document_id;
        $this->account_id = $entry->account_id;
        $this->debit = $entry->debit;
        $this->credit = $entry->credit;
        $this->openModal();
    }

    public function delete($id)
    {
        Entry::where('id',$id)->where('company_id',session('company_id'))->first()->delete();
        session()->flash('message', 'Record Deleted Successfully.');
    }
}

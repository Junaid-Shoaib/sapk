<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Account;
use App\Models\Entry;
use App\Models\Year;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use App\Rules\Range;

class Documents extends Component
{
    use WithPagination;

    public $docs, $accounts, $ref, $date, $description, $type_id, $type, $types, $at_id;
    public $ite=0;
    public $isOpen = 0;
    public $account_id= [];
    public $debit=[];
    public $credit=[];
    public $inputs=[];
    public $i = 1;
    public $latest;
    public $diff, $dtotal, $ctotal;
    public $year;
    public $search1 = '';
    public $search2 = '';
    public $search3 = '';
    public $search4 = '';

    protected $rules = [
        'ref' => 'required',
        'date' => ['required','date'],
        'description' => 'required',
        'type_id' => 'required',
        'account_id.0' => 'required',
        'debit.0' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'credit.0' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'account_id.1' => 'required',
        'debit.1' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'credit.1' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'account_id.*' => 'required',
        'debit.*' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'credit.*' => 'required|regex:/^\d+(\.\d{1,2})?$/',
        'diff' => 'gte:0|lte:0',
    ];

    protected $messages = [
        'diff.gte' => 'The entry is not balanced!',
        'diff.lte' => 'The entry is not balanced!',
        'account_id.*.required' => 'The head of account has not been selected!',
        'debit.*.required' => 'Debit amount has not been entered!',
        'credit.*.required' => 'Credit amount has not been entered!',
        'debit.*.regex' => 'Amount must a numeric value!',
        'credit.*.regex' => 'Amount must a numeric value!',
    ];


    public function mount(){
        $this->year =  Year::where('company_id',session('company_id'))->where('enabled',1)->first();
        $this->docs = Document::where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->get();
        $this->types = DocumentType::where('company_id',session('company_id'))->get();
        $this->type_id = DocumentType::where('company_id',session('company_id'))->first()->id;
        $this->type = $this->types->where('id',$this->type_id)->first();
        $this->search3 = $this->year->begin;
        $this->search4 = $this->year->end;
    }

    public function add($i)
    {
        $i = $i + 1;
        $this->i = $i;
        array_push($this->inputs ,$i);
        array_push($this->account_id ,'');
        array_push($this->debit ,'0');
        array_push($this->credit ,'0');
    }

    public function remove($i)
    {
        $j = $i+2;
        unset($this->inputs[$i]);
        unset($this->account_id[$j]);
        $this->debit[$j]=0;
        $this->credit[$j]=0;
    }

    public function render()
    {
        $this->docs = Document::where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->get();
        $this->type = $this->types->where('id',$this->type_id)->first();
        if(! $this->date){
            if(count($this->docs->where('type_id',$this->type_id))){
                $this->date = Document::where('type_id',$this->type_id)->where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->latest()->first()->date;
            } else {
                $this->date = $this->year->begin;
            }
 //           $this->date = Document::where('type_id',$this->type_id)->where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->latest()->first()->date;
        }
        if(!$this->at_id){
            if(count($this->docs->where('type_id',$this->type_id))){
                $lastref = Document::where('type_id',$this->type_id)->where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->latest()->first()->ref;
                $expNum=explode('/', $lastref);
                $this->latest = $expNum[3];
                ++$this->latest;
                } else {
                    $this->latest = 1;      // for first voucher. only works on fresh database starting from id=1 or else error in entries
            }
            $nowdate = Carbon::parse($this->date);
            $this->ref = $this->type->prefix . '/' . $nowdate->year . '/' . $nowdate->month . '/' .$this->latest;
        }
        else{
                $ref = Document::where('id',$this->at_id)->where('company_id',session('company_id'))->first()->ref;
                $this->ref = $ref;
        }

        $this->total();
        if(($this->search3 < $this->year->begin) || ($this->search3 > $this->year->end)){$this->search3 = $this->year->begin;}
        if(($this->search4 < $this->year->begin) || ($this->search4 > $this->year->end)){$this->search4 = $this->year->end;}
        return view('livewire.sa.documents',['docss'=>Document::where('company_id',session('company_id'))->where('ref','like','%' . $this->search1 . '%')->where('description','like','%' . $this->search2 . '%')->where('date','>=',$this->search3)->where('date','<=',$this->search4)->orderBy('date')->orderBy('ref')->paginate(10)]);
    }

/*
    public function updated()
    {
        $this->validateOnly([
            'search3' => ['required','date', new Range],
//            'search4' => [new Range], 
        ]);
    }
*/
    public function create()
    {
        $this->resetInputFields();
        $this->debit[0]='0';
        $this->debit[1]='0';
        $this->credit[0]='0';
        $this->credit[1]='0';
        $this->accounts = Account::where('company_id',session('company_id'))->get();
//        $this->date = Carbon::today()->toDateString();
        if(count($this->docs->where('type_id',$this->type_id))){
            $this->date = Document::where('type_id',$this->type_id)->where('company_id',session('company_id'))->where('date','>=',$this->year->begin)->where('date','<=',$this->year->end)->latest()->first()->date;
        } else {
            $this->date = $this->year->begin;
        }
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetPage();
    }

    private function resetInputFields(){
        $this->ref = '';
        $this->date = '';
        $this->description = '';
        $this->at_id = '';
        $this->inputs = [];
        $this->account_id = [];
        $this->debit = [];
        $this->credit = [];
        $this->i=1;
    }

    public function store()
    {
        $this->validate();
        $this->validate([
            'date' => [new Range],
        ]);

        DB::transaction(function () {
            $current = Document::create([
                'ref' => $this->ref,
                'date' => $this->date,
                'description' => $this->description,
                'type_id' => $this->type_id,
                'company_id' => session('company_id'),
            ]);

            foreach ($this->account_id as $key => $value) {
                Entry::create(['document_id' => $current->id, 'account_id' => $this->account_id[$key], 'debit' => $this->debit[$key], 'credit' => $this->credit[$key], 'company_id' => session('company_id')]);
            }
        });

        session()->flash('message', 
            $this->at_id ? 'Entry Updated Successfully.' : 'Entry Created Successfully.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function storee()
    {
        $this->validate();
        $this->validate([
            'date' => [new Range],
        ]);

        $doc = Document::where('id',$this->at_id)->where('company_id',session('company_id'))->first();

        DB::transaction(function () use ($doc) {

            $entries = Entry::where('document_id',$doc->id)->where('company_id',session('company_id'))->get();
            foreach($entries as $entry){
                $entry->delete();
            }

            $doc->date = $this->date;
            $doc->description = $this->description;
            $doc->save();

            foreach ($this->account_id as $key => $value) {
                Entry::create(['document_id' => $doc->id, 'account_id' => $this->account_id[$key], 'debit' => $this->debit[$key], 'credit' => $this->credit[$key], 'company_id' => session('company_id')]);
            }
        });

        session()->flash('message', 
            $this->at_id ? 'Entry Updated Successfully.' : 'Entry Recoreded Successfully.');

        $this->closeModal();
        $this->resetInputFields();
    }

    public function edit($id)
    {
        $this->resetInputFields();
        $this->at_id = $id;
        $doc = Document::where('id',$this->at_id)->where('company_id',session('company_id'))->first();
        $num = count($doc->entries);
        if($num>2){
            $this->i = $num-1;
            for($i=0;$i<($num-2);$i++){
                $this->inputs[$i] = $i+2; 
            }
        }
        $this->ref = $doc->ref;
        $this->date = $doc->date;
        $this->description = $doc->description;
        $this->type_id = $doc->type_id;
        $this->accounts = Account::where('company_id',session('company_id'))->get();
        foreach($doc->entries as $key => $value){
            $this->account_id[$key] = $value->account_id;
            $this->debit[$key] = $value->debit;
            $this->credit[$key] = $value->credit;
        }
        $this->openModal();
    }

    public function delete($id)
    {
        DB::transaction(function () use ($id) {
            $entries = Entry::where('document_id',$id)->where('company_id',session('company_id'))->get();
            foreach($entries as $entry){
                $entry->delete();
            }
            $doc=Document::where('id',$id)->where('company_id',session('company_id'))->first();
            $doc->delete();
            session()->flash('message', 'Entry Deleted Successfully.');
        });
    }

    function total(){
        $dtotal=0;
        $ctotal=0;
        try{
            for($j=0;$j<count($this->debit);$j++){
                if($this->debit[$j]){
                    $dtotal = $dtotal + $this->debit[$j];
                }
            }
            for($j=0;$j<count($this->credit);$j++){
                if($this->credit[$j]){
                    $ctotal = $ctotal + $this->credit[$j];
                }
            }
        $this->dtotal = $dtotal;
        $this->ctotal = $ctotal;
        $this->diff = $this->dtotal - $this->ctotal;
        } catch (\Exception $e){
            return $e->getMessage();
        }
    }
}

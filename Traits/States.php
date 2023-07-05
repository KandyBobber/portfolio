<?php

namespace App\Http\Controllers\Traits;
use App\Models\Mark;
use App\Models\People;
use App\Models\PeopleMark;
use App\Models\Rank;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait States
{
    use Filters;
    /**
     * Виводить повний штат можливі фільтри, можливі параметри ['onlyState'],['withPeople'],['onlyWithPeople'],['withMarks']
     * @param \Illuminate\Http\Request $request
     * @param array $paramArr
     * @return mixed
     */
    public function allState(Request $request, array $paramArr = ['withPeople', 'withMarks']) {
        /**
         * Можливі фільтри просто потрібно передати значення request
         * state
         * all - true
         * @date - date
         * @stateName - like
         * @base - '='
         * @company - '='
         * @num - '='
         * @stateRound - '='
         * @stateRank - '='
         * @delete - true|false
         * 
         * people
         * @pibFind - like
         * @rankShort - '='
         * @service_type - '='
         * 
         * mark
         * @endDate - date
         * @dop - number
         * @mark
         */

        $onlyState = (in_array('onlyState', $paramArr)) ? true : false;
        $withPeople = (in_array('withPeople', $paramArr)) ? true : false;
        $onlyWithPeople = (in_array('onlyWithPeople', $paramArr)) ? true : false;
        $withMarks = (in_array('withMarks', $paramArr)) ? true : false;
        
        $request->merge(['route' => (!empty($paramArr['route']) ? $paramArr['route'] : 'state')]);
        $request->merge(['method' => (!empty($paramArr['method']) ? $paramArr['method'] : 'get')]);

        //getting standart date
        if (!isset($request->date)) $request->merge(['date' => Carbon::today()->format('Y-m-d')]);
        $request->merge(['paramArr' => $paramArr]);

        $dates = $this->dateChange($request);

        //getting all state
        $states = $this->getStates(clone $request);

        if ($withPeople) {
            $states = $this->withPeople($request, $states);

            if ($onlyWithPeople) {
                $states = $this->filter($states, $request, [
                    ['colName' => 'pib', 'value' => '', 'type' => '!'],
                ]);
            }

            if ($withMarks) {
                $states = $this->withMarks($request, $states);
            }
        }

        $filters = clone $request;

        if ($onlyState) return $states;
        else {
            $lists = $this->lists();
            return compact('states', 'filters', 'lists', 'dates');
        }
    }

    protected function getStates($request) {
        //unset for filter
        $request = clone $request;
        if (!empty($request->all)) $request->offsetUnset('date');

        $states = State::with(['rank'])
        ->orderBy('num')
        ->get();

        //for not delete
        $stateNotDelete = People::with([
            'states' => function ($query) {
                $query->select('states.id', 'people_morphs.change_date');
                $query->whereNull('people_morphs.deleted_at');
                $query->orderBy('people_morphs.change_date', 'desc');
            },
        ])
        ->whereHas('states')
        ->get();

        $allRanks = Rank::get();

        foreach ($states as $state_key => $state) {
            /**
             * State additional options
             * @stateRound
             * @stateRank
             * @delete
             */
            foreach ($allRanks as $rank) {
                if ($rank->id === $state->rank_id) {
                    $states[$state_key]->stateRound = $rank->round;
                    $states[$state_key]->stateRank = $rank->short;
                }
            }

            //test on delete
            $states[$state_key]->delete = true;
            foreach ($stateNotDelete as $people_i) {
                foreach ($people_i->states as $value) {
                    if ($value->id === $state->id) {
                        $states[$state_key]->delete = false;
                        break 2;
                    }
                }
            }
        }

        return $this->filter($states, $request, 'statesFilters');
    }

    /**
     * Getting all needed list for select or checkBox
     * @return array
     */
    public function lists() {
        $ranksArr = [];
        foreach (Rank::orderBy('level')->get() as $item) {
            $ranksArr[$item->id] = $item->short;
        }

        $baseArr = [];
        foreach (State::select('base')
        ->groupBy('base')
        ->get() as $item) {
            $baseArr[] = $item->base;
        }

        $companyArr = [];
        foreach (State::select('company')
        ->groupBy('company')
        ->get() as $item) {
            $companyArr[] = $item->company;
        }

        $ranksArrRound = [];
        foreach (Rank::select('round')
        ->groupBy('round')
        ->get() as $item) {
            $ranksArrRound[] = $item->round;
        }

        $marksArr = [];
        foreach (Mark::all() as $item) {
            $marksArr[$item->id] = $item->name;
        }

        $service_typeArr = ['контракт', 'мобілізований', 'строкова', 'не служить'];

        return compact('ranksArr', 'baseArr', 'companyArr', 'ranksArrRound', 'service_typeArr', 'marksArr');
    }

    protected function dateChange($request) {
        $dop = !empty($request->dop) ? $request->dop : 0;
        $request->offsetUnset('dop');

        if (!empty($request->date)) {
            $startDate = $request->date;
            $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDays(-$dop);
        } else {
            $now = Carbon::now();
            $monthFirst = $now->format('m');
            $yearFirst = $now->format('Y');
            $startDate = Carbon::createFromFormat('Y-m-d', "$yearFirst-$monthFirst-01")->addDays(-$dop);
        }
        $request->offsetUnset('date');
        $request->merge(['date' => $startDate->format('Y-m-d')]);


        if (!empty($request->endDate) && $startDate->format('Y-m-d') < $request->endDate) {
            $endDate = $request->endDate;
            $endDate = Carbon::createFromFormat('Y-m-d', $endDate)->addDays($dop);
        } else {
            $monthSecond = (($startDate->format('m')+1) > 12) ? 1 : $startDate->format('m')+1;
            $yearSecond = $monthSecond === 1 ? $startDate->format('Y') + 1 : $startDate->format('Y');
            $endDate = Carbon::createFromFormat('Y-m-d', "$yearSecond-$monthSecond-01")->addDays(-1);
            
            if ($startDate < ((clone $endDate)->addDays($dop))) {
                $endDate->addDays($dop);
            }
        }
        $request->offsetUnset('endDate');
        $request->merge(['endDate' => $endDate->format('Y-m-d')]);

        return compact('startDate', 'endDate');
    }

    protected function withMarks($request, $states) {
        $allmarks = Mark::get();
        //отримую потрібний проміжок
        $monthMarks = PeopleMark::
        join('marks', 'people_mark.mark_id', '=', 'marks.id')
        ->select('people_mark.*', 'marks.name', 'marks.description', 'marks.weapon', 'marks.time', 'marks.location', 'marks.color')
        ->orderBy('people_mark.change_date')
        ->where('people_mark.change_date', '>=', $request->date)
        ->where('people_mark.change_date', '<=', $request->endDate)
        ->get();

        // отримую останю зміну до проміжку
        $latestMarks = DB::table('people_mark')
        ->select(DB::raw('people_id, MAX(change_date) AS latest_date'))
        ->where('people_mark.change_date', '<', $request->date)
        ->groupBy('people_id');

        $oldMarks = PeopleMark::
        join('marks', 'people_mark.mark_id', '=', 'marks.id')
        ->joinSub($latestMarks, 'latest_marks', function ($join) {
            $join->on('people_mark.people_id', '=', 'latest_marks.people_id')
                ->on('people_mark.change_date', '=', 'latest_marks.latest_date');
        })
        ->select('people_mark.*', 'marks.name', 'marks.description', 'marks.weapon', 'marks.time', 'marks.location', 'marks.color')
        ->get();

        foreach ($states as $state_key => $state) {
            if (isset($state->people_id)) {
                $monthMarksArr = [];
                $monthMarksArr['oldMark'] = $allmarks[0];
                $monthMarksArr['oldMark']->people_id = $states[$state_key]->people_id;
                $monthMarksArr['oldMark']->edited = 'Більш старі записи відсутні, цей додано системою';
                $monthMarksArr['oldMark']->message = 'Додано системою';

                // Порівнюю записи та приєдную їх до особи
                foreach ($oldMarks as $oldMark) {
                    if ($oldMark->people_id === $states[$state_key]->people_id) {
                        $monthMarksArr['oldMark'] = $oldMark;
                    }
                }
                
                foreach ($monthMarks as $monthMark) {
                    if ($monthMark->people_id === $states[$state_key]->people_id) {
                        $monthMarksArr[$monthMark->change_date] = $monthMark;
                    }
                }
                
                $states[$state_key]->setAttribute('monthMarks', collect($monthMarksArr));
            }
        }

        // dd($states);
        return $this->filter($states, $request, [
            [
                ['colName' => 'name', 'requestName' => 'mark'],
            ]
        ], ['monthMarks']);
    }

    protected function withPeople($request, $states) {
        $people = $this->getPeople($request);
        /**
         * State additional options
         * @pib
         * @pibLong
         * @rankShort
         * @service_type
         */
        foreach ($states as $state_key => $state) {
            //all people info
            $states[$state_key]->pib = '';
            $states[$state_key]->pibLong = '';
            $states[$state_key]->rankShort = '';
            $states[$state_key]->service_type = '';
            //add info
            foreach ($people as $people_i) {
                if (!empty($people_i->states[0]->id) && ($people_i->states[0]->id === $state->id)) {

                    // Особиста інформація
                    if (!empty($people_i->subjects[0])) {
                        $info = $people_i->subjects[0];

                        $states[$state_key]->pib .= $info->last_name.' '.mb_substr($info->first_name,1,1).'.'.mb_substr($info->middle_name,1,1).'.';

                        $states[$state_key]->pibLong .= "$info->last_name $info->first_name $info->middle_name";
                    }
        
                    // Звання
                    if (!empty($people_i->ranks[0])) {
                        $info = $people_i->ranks[0];
                        $states[$state_key]->rankShort .= "$info->short";
                        $states[$state_key]->service_type .= "$info->service_type";
                    }

                    $states[$state_key]->people_id = $people_i->id;
                    break;
                }
            }
        }

        return $this->filter($states, $request, 'statesPeopleFilters');
    }

    protected function getPeople($request) {
        //getting all people
        $people = People::with(['subjects' => function ($query) {
                $query->select('id', 'people_id', 'last_name', 'first_name', 'middle_name',
                'birth_date', 'change_date');
                $query->orderBy('change_date', 'desc');
            },
            'ranks' => function ($query) {
                $query->select('ranks.*', 'people_morphs.change_date', 'people_morphs.service_type');
                $query->whereNull('people_morphs.deleted_at');
                $query->orderBy('people_morphs.change_date', 'desc');
            },
            'states' => function ($query) {
                $query->select('states.id', 'people_morphs.change_date');
                $query->whereNull('people_morphs.deleted_at');
                $query->orderBy('people_morphs.change_date', 'desc');
            },
        ])
        ->whereHas('states')
        ->get();

        //unseting newest info out of range
        if (isset($request->date)) {
            foreach ($people as $people_key => $people_i) {
                $people[$people_key]->subjects = $this->filter($people_i->subjects, $request, 'actualPeople');
                $people[$people_key]->states = $this->filter($people_i->states, $request, 'actualPeople');
                $people[$people_key]->ranks = $this->filter($people_i->ranks, $request, 'actualPeople');
            }
        }

        if (!empty($request->service_type)) {
            return $people;
        } else {
            return $this->filter($people, $request, [
                ['colName' => 'service_type', 'value' => 'не служить', 'type' => '!'],
            ], ['ranks']);
        }
    }
}
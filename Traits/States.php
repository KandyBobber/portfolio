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
     * Виводить повний штат,можливі параметри ['onlyState','withPeople','onlyWithPeople','withMarks', 'route' => 'state']
     * Та Route де передається шлях який вставити в фільтри для повернення в цей самий вивід
     * @param \Illuminate\Http\Request $request є фільтраційним
     * @param array $paramArr
     * @return mixed
     */
    public function allState(Request $request, array $paramArr = ['withPeople', 'withMarks']) {
        // dd($request);
        // dd('01.01.2020' < '02.01.2020');
        /**
         * Можливі фільтри, просто потрібно передати значення в request з назвою змінної що вказана в списку
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

        // Перевіряю параметри
        $onlyState = (in_array('onlyState', $paramArr)) ? true : false; // Повернути тільки штат
        $withPeople = (in_array('withPeople', $paramArr)) ? true : false; // Штат з людьми
        $onlyWithPeople = (in_array('onlyWithPeople', $paramArr)) ? true : false; // Тільки там де є люди
        $withMarks = (in_array('withMarks', $paramArr)) ? true : false; // З мітками
        
        $request->merge(['route' => (!empty($paramArr['route']) ? $paramArr['route'] : 'state')]);
        $request->merge(['method' => (!empty($paramArr['method']) ? $paramArr['method'] : 'get')]);
        $request->merge(['paramArr' => $paramArr]);

        // Формую дати
        $dates = $this->dateChange($request, $withMarks);

        // Отримую штат
        $states = $this->getStates(clone $request);

        // Якщо з людьми та мітками
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

    /**
     * Отримую дані по штату та фільтрую їх
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Collection array
     */
    protected function getStates(Request $request) {
        $states = State::with(['rank']);

        // Обмеження по даті, якщо немає отримати всі
        if (empty($request->all)) {
            $states = $states->where('active', '<=', $request->date)
            ->where('no_active', null)
    
            ->orWhere('active', '<=', $request->date)
            ->where('no_active', '>', $request->date);
        }

        $states = $states->orderBy('num')
        ->get();

        // Отримую прив'язку людей щоб зрозуміти які посади можна видалити
        $stateNotDelete = People::with([
            'states' => function ($query) {
                $query->select('states.id', 'people_morphs.change_date');
                $query->whereNull('people_morphs.deleted_at');
                $query->orderBy('people_morphs.change_date', 'desc');
            },
        ])
        ->whereHas('states')
        ->get();

        // Отримую звання
        $allRanks = Rank::get();

        // Пов'язую штат з додатковими даними
        foreach ($states as $state_key => $state) {
            /**
             * State additional options
             * @stateRound
             * @stateRank
             * @delete
             */
            // Так це можна перенести в запит і отримувати через join
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

        // фільтрую штатні дані
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

    /**
     * Метод що створює потрібні дані по даті
     * @param \Illuminate\Http\Request $request
     * @param bool $withMarks
     * @return array
     */
    protected function dateChange(Request $request, $withMarks) {

        // Перевіряю та ініціалізую розширення дати
        $dop = !empty($request->dop) ? $request->dop : 0;
        $request->offsetUnset('dop');

        // Первіряю та ініціалізую дату початку
        if (!empty($request->date)) {
            $startDate = $request->date;
            $startDate = Carbon::createFromFormat('Y-m-d', $startDate)->addDays(-$dop);
        } else {
            if ($withMarks) {
                $now = Carbon::now();
                $monthFirst = $now->format('m');
                $yearFirst = $now->format('Y');
                $startDate = Carbon::createFromFormat('Y-m-d', "$yearFirst-$monthFirst-01")->addDays(-$dop);
            } else {
                $startDate = Carbon::today();
            }
        }
        $request->offsetUnset('date');
        $request->merge(['date' => $startDate->format('Y-m-d')]);

        // Дату кінця
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

    /**
     * Отримую та прив'язую до штату мітки з даними про людину по дню
     * @param \Illuminate\Http\Request $request
     * @param mixed $states
     * @return mixed
     */
    protected function withMarks(Request $request, $states) {
        $allmarks = Mark::get();

        // отримую потрібний проміжок
        $monthMarks = PeopleMark::
        join('marks', 'people_mark.mark_id', '=', 'marks.id')
        ->select('people_mark.*', 'marks.name', 'marks.description', 'marks.weapon', 'marks.time', 'marks.location', 'marks.color')
        ->orderBy('people_mark.change_date')
        ->where('people_mark.change_date', '>=', $request->date)
        ->where('people_mark.change_date', '<=', $request->endDate)
        ->get();

        // отримую список останніх зміних до потрібного проміжку, щоб розуміти з чого почати
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

        // Підкидую мітки в штат
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

        return $this->filter($states, $request, [
            [
                ['colName' => 'name', 'requestName' => 'mark'],
            ]
        ], ['monthMarks']);
    }

    
    /**
     * Отримую людей та підкидую їх до штату по потребі
     * @param \Illuminate\Http\Request $request
     * @param mixed $states
     * @return mixed
     */
    protected function withPeople(Request $request, $states) {
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
                if (!empty($people_i->states->first()->id) && ($people_i->states->first()->id === $state->id)) {
                    // Особиста інформація
                    if (!empty($people_i->subjects->first())) {
                        $info = $people_i->subjects->first();

                        $states[$state_key]->pib .= $info->last_name.' '.mb_substr($info->first_name,1,1).'.'.mb_substr($info->middle_name,1,1).'.';

                        $states[$state_key]->pibLong .= "$info->last_name $info->first_name $info->middle_name";
                    }
        
                    // Звання
                    if (!empty($people_i->ranks->first())) {
                        $info = $people_i->ranks->first();
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

    /**
     * Отримує людей для штату
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    protected function getPeople(Request $request) {
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
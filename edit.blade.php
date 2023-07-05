@extends('layouts.app')

@section('title-block'){{$subjectData->title}}@endsection

@push('components')
    @include('functions.subjectFun')
@endpush

@section('content')
<div class="col-12">
    @stack('components')
    <h2>{{$subjectData->title}}<small>, перейти на сторінку 
        <a href="{{ route('changeState.edit', ['id' => $id, 'type' => 'states']) }}" class="text-decoration-none">
            Посади
        </a>
    </small></h2>

    <form action="{{ route('changeRank.create') }}" method="post" class="border-bottom">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{$subjectData->id}}">
        <div class="d-flex gap-2 mb-3">
            {{inputColls([
                'change_date' => [
                    'object' => $subjectData,
                    'dataType' => 'date',
                    'divClass' => 'col-md-3', 
                    'placeholder' => '',
                ],
            ])}}
            {{selectColls('people_morph_id', ['data' => NULL, 'divClass' => 'col-md-4', 'keyValue' => 1], $selectData)}} 
            {{inputColls([
                'order' => [
                    'object' => $subjectData,
                    'dataType' => 'text', 
                    'divClass' => 'col-md-4', 
                    'placeholder' => '',
                ],
            ])}}
            <button type="submit" class="btn btn-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3v-3z"/>
                </svg>
            </button>
        </div>    
        <div class="d-flex gap-2 mb-3">
            {{selectColls('service_type', ['object' => $subjectData, 'divClass' => 'col-md-3'], [
                'контракт', 'мобілізований', 'строкова', 'не служить'
            ])}}
        </div>
    </form>
    <br>
    <br>
    @if (!empty($subjectData->data[0]))
    <form action="{{ route('changeRank.update') }}" method="post" class="border-up">
        @csrf
        @method('PATCH')
        @foreach ($subjectData->data as $item)
        <input type="hidden" name="id[]" value="{{$item->pivot->id}}">
        <div class="d-flex gap-2 mb-1">
            {{inputColls([
                'change_date' => [
                    'object' => $item->pivot,
                    'dataType' => 'date',
                    'divClass' => 'col-md-3', 
                    'placeholder' => '',
                    'multy' => '',
                ],
            ])}}
            {{selectColls('people_morph_id', ['object' => $item->pivot, 'divClass' => 'col-md-4', 'keyValue' => 1, 'multy' => ''], $selectData)}} 
            {{inputColls([
                'order' => [
                    'object' => $item->pivot,
                    'dataType' => 'text', 
                    'divClass' => 'col-md-4', 
                    'placeholder' => '',
                    'multy' => '',
                ],
            ])}}
            <button class="btn btn-danger in-form-value">                    
                <input type="hidden" name="destroy_id" value="{{$item->pivot->id}}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"></path>
                </svg>
            </button>
        </div>  
        <div class="d-flex gap-2 mb-3">
        {{selectColls('service_type', ['object' => $item->pivot, 'divClass' => 'col-md-3', 'multy' => ''], [
            'контракт', 'мобілізований', 'строкова', 'не служить'
        ])}}
        <p class="col-md-9">Редагування: {{$item->pivot->edited}}</p>
        </div>
        @endforeach
        <button type="submit" class="btn btn-success">Зберегти</button>
    </form>
    @endif
</div>
<form action="{{ route('changeRank.destroy') }}" method="post" id="SELECT_FORM">
    @csrf
    @method('DELETE')
</form>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var id_values = document.querySelectorAll('button.in-form-value');
        var form = document.getElementById('SELECT_FORM');
        id_values.forEach(element => {
            element.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                var confirmation = confirm("Видалити це звання?");
                if (confirmation) {
                    var input = element.querySelector('input');
                    input.parentNode.removeChild(input);
                    form.appendChild(input);
                    form.submit();
                }
            });
        });
    });
</script>
@endsection




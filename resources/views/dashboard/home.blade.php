@extends('layouts.app')
@include('layouts.old-nav')

@section('content')
<div class="container">
<hr>
    <div class="row my-5">
        @if(auth()->check())
        <div class="col-sm-12">
            <a class="btn btn-primary btn-lg" style="background-color: #0000FF" onclick="createRoom()">Criar nova reunião</a>
        </div>
        @endif
        <div class="col-sm-5 mt-3">
            <label for="meeting-id">Entrar em uma reunião</label>
            <div class="input-group mb-3">
                <input type="text" name="meeting-id" id="meeting-id" class="form-control" placeholder="Id. da reunião" aria-label="Id. da reunião" aria-describedby="button-addon2">
                <a class="btn  btn-success"  type="button" onclick="findMeeting()" id="find-meeting">Entrar</a>
              </div>
        </div>
    </div>

</div>
<script>
    function findMeeting()
    {
        let id = document.getElementById('meeting-id').value
        if(!id){
            return false;
        }
        fetch(window.origin.toString()+'/' +id,{
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then( (res) =>{
            return res.json();
        }).then( (json) => {
            if(json.exists){
                window.location = window.origin.toString()+'/' +id
                
            }else{
               showError(document.getElementById('meeting-id'),'Não foi possivel encontrar a reunião, verifique se o ID. está correto');
            }
        })
    }

    @if(auth()->check())
        function createRoom()
        {
            let id = `${randomId()}${randomId()}-${randomId()}${randomId()}-${randomId()}${randomId()}`
            window.location = window.origin.toString() + '/' +id;
        }
    @endif

    function showError(input,error,miliseconds = 5000)
    {
        let errorId = randomId();
        let alert = `<div id="error-alert-${errorId}" class="alert alert-warning alert-dismissible fade show" role="alert">
            <small>${error}</small>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>`
    input.parentNode.insertAdjacentHTML('afterend',alert);
    setTimeout(() => {
        document.querySelector('#error-alert-'+errorId + ' > .btn-close').click();
    }, miliseconds);
    }

    function randomId()
    {
  return Math.floor((1 + Math.random()) * 0x10000)
      .toString(16)
      .substring(1);
    }

</script>
@endsection
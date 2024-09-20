<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
    @if(\App\Domain::checkWhiteLabelUrl())
      EBMail Meet 
        @else
            EBMail Meet
        @endif
    </title>
    <script>window.Laravel = {csrfToken: '{{ csrf_token() }}'}</script>

  <!-- Icons -->
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" 
    integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">  
   
   

    @if(\App\Domain::checkWhiteLabelUrl())
    @include('layouts.white-label') 
    <link rel="icon" href="{{ asset('assets/images/citex.png') }}">

      <script> 
            const whiteLabel = true; 
      </script>

@else
<script> 
  const whiteLabel = false; 
</script>

<link rel="icon" href="{{ asset('assets/images/citex.png') }}">
@endif
 
  </head>
    
  
  <body> 
    <div id="root"></div>
    <script src="{{asset('js/app.js')}}">

    </script>   

  </body>
</html> 
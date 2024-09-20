 
 <!-- <style>
    #form{
    }
    body {
    }
    .navbarNew{
      display:flex;
      flex-direction:row;
      height:15%;
      justify-content:space-between;
      background-color:#fff;
      padding:1rem 5%;
      align-items:center;
    }
    .navbarNew img{
      width:250px;
      height:fit-content;
    } 
    .navbar-toggler span{
      background-color:blue !important;
    }
    .nav-link{
      background-color:blue !important;
    }
    .showUser{
      text-align:end;
    } 
  .showMenuLat{ 
    transform: translate(-62px, 38px) !important;
  }
    @media screen and (max-width:1020px) {
      .navbarNew {
      display:flex;
      justify-content:center;
      align-items:center;
      flex-direction:column;
      padding: 3rem 0; 
    }
    .nav-link{
     margin-top:1.5rem;
    }
    }
 
</style> -->
<!-- <nav class="navbarNew" >
  <img src="{{asset('assets/images/03_COLORIDO.png')}}" />  
  @if(auth()->check())
          <li class="nav-item dropdown d-flex">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span style="font-weight: bold;color:white">{{auth()->user()->name}}</span>
              </a>
              <ul class="dropdown-menu mr-3" aria-labelledby="navbarDropdown">
  
                  <li><hr class="dropdown-divider"></li>
                  <form method="POST" action="{{route('logout')}}">
                    @csrf
                    <button action="submit"class="dropdown-item  btn-danger"><i class="fas fa-sign-out-alt"></i>  Sair</button>
                    </form>            
                </ul>
            </li>
            @endif
  
          @if(session()->get('guest.auth'))
          <li class="nav-item dropdown d-flex">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span style="font-weight: bold;color:white">Convidado</span>
            </a>
            <ul class="dropdown-menu mr-3 showMenuLat" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item disabled" >Logado como visitante</a></li>
                <li><hr class="dropdown-divider"></li>
        
                  <a href="{{route('login')}}" class="dropdown-item  btn-danger"><i class="fas fa-sign-out-alt"></i> Entrar</a>
              </ul>
          </li>
          @endif 
</nav>    -->
      <!-- <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
  
        </ul>
     
    </div>
  </nav> -->
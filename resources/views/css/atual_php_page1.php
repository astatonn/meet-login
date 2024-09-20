[ 
 <style>
    #form{
    }
    body {
    }
.navbarNew{
  background-color:#fff;
  padding:1rem 5%;
}
 
</style>
<nav class="navbarNew" >
<img width="250px" src="{{asset('assets/images/03_COLORIDO.png')}}" /> 
</nav> 
      <a class="navbar-brand"  style="font-weight: bold; color:white"> 
        </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
  
        </ul>
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
            <ul class="dropdown-menu mr-3" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item disabled" >Logado como visitante</a></li>
                <li><hr class="dropdown-divider"></li>
        
                  <a href="{{route('login')}}" class="dropdown-item  btn-danger"><i class="fas fa-sign-out-alt"></i> Entrar</a>
              </ul>
          </li>
          @endif 
    </div>
  </nav>]
<style>
  body{
    overflow: hidden;
  }
</style>

<div class="">
  <div class="row">
      <div class="col-sm-auto sticky-top" style="background-color: #111eff">
          <div class="d-flex flex-sm-column flex-row flex-nowrap align-items-center sticky-top">
              <a href="/" class="d-block p-3 link-dark text-decoration-none" title="" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="Icon-only">
                  <i class="bi-bootstrap fs-1"></i>
              </a>
              <ul class="nav nav-pills nav-flush flex-sm-column flex-row flex-nowrap mb-auto mx-auto text-center align-items-center">

              </ul>
              <div class="dropdown">
                  <a href="#" class="d-flex align-items-center justify-content-center p-3 link-dark text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas text-light fa-user-circle"></i>
                                    </a>
                                    
                  <ul class="dropdown-menu text-small" aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item disabled"><span>{{auth()->user()->name}}</span></a></li>


                      <li><a class="dropdown-item" href="#">New project...</a></li>
                      <li><a class="dropdown-item" href="#">Settings</a></li>
                      <li><a class="dropdown-item" href="#">Profile</a></li>
                  </ul>
              </div>
          </div>
      </div>
      <div class="col-sm  min-vh-100">
      </div>
  </div>
</div>
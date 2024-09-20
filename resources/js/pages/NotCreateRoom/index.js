import React, { useEffect, useState } from 'react'
import AnimatedPage from '../../components/AnimatedPage/AnimatedPage'
import Header from "../../components/Header"
import "./NotCreateRoom.css"
import { store, USER_LOGGED } from "../../store/store"
import InputComponent from '../../components/InputComponent'
import { captureData } from '../../helpersfunctions/helpersFunctions'
import { ToastContainer, toast } from 'react-toastify';
import { useNavigate } from 'react-router-dom'
import citexLogo from "../../../../public/assets/images/citex.png"

export default function NotCreateRoom() {

  const [showButtons, setShowButtons] = useState(false)
  const [modal, setModal] = useState(false)
  const navigate = useNavigate();

  useEffect(() => {
    if (store.getState().logged != undefined && store.getState().email != undefined) {
      console.log(store.getState().email)
      setShowButtons(false)
    } else {
      console.log('no email')
      setShowButtons(true)
    }
  }, [modal])

  const toggleActivy = (e) => {
    document.getElementById('nav-icone').classList.toggle('open')
    if (document.getElementById('nav-icone').classList.contains('open')) {
      document.getElementById('dropdown-menu').classList.add('is-block')
    } else {
      document.querySelector('#dropdown-menu').classList.remove('is-block')
    }
  }

  const enterToVis = () => {
    let modal = document.getElementById('image-modal')
    modal.classList.toggle('is-active')

    if (document.getElementById('dropdown-menu').classList.contains('is-block')) {
      document.getElementById('dropdown-menu').classList.remove('is-block')
    }
    if (document.getElementById('nav-icone').classList.contains('open')) {
      document.getElementById('nav-icone').classList.remove('open')
    }
  }

  const postFormModal = (e) => {
    e.preventDefault();
    let captureForm = document.querySelector('.modal-table')
    let errorInFormRemove = captureForm.querySelectorAll('.input')

    errorInFormRemove.forEach(k => {
      k.classList.remove('is-danger')
      k.parentElement.nextElementSibling.innerHTML = ""
    })

    let captureInputs = captureData();
    e.target.classList.add('is-loading')
    axios.post(window.origin + '/services/login', captureInputs)
      .then((response) => {
        console.log(response.data)
        if (response.data.status) {
          store.dispatch({ type: USER_LOGGED, logged: true, email: document.getElementById('email').value })
          toast.success(response.data.message, { autoClose: 1000 })
          document.getElementById('password').classList.add('is-success')
          document.getElementById('email').classList.add('is-success')
          setModal(!modal)
          setTimeout(enterToVis, 800)
          window.location.reload()
        }
      })
      .catch((error) => {
        if (error.response != undefined) {
          if (error.response.status != undefined && error.response.status == '422') {
            console.log(error.response)
            for (let i in error.response.data.errors) {
              document.getElementById(i).classList.add('is-danger')
              document.getElementById(i).parentElement.nextElementSibling.innerHTML = error.response.data.errors[i]
            }
          }
        }
      })
      .finally(() => {
        e.target.classList.remove('is-loading')
      })
  }

  const logoutCall = () => {
    window.axios.post('/services/logout')
      .then((response) => {
        if (response.data.status) {
          store.dispatch({ type: USER_LOGGED, logged: false })
          navigate('/login')
        }

      })
      .catch((error) => {
        if (error.response != undefined) {
          if (error.response.status != undefined && error.response.status == '401') {
            console.log('nao autorizado')
          }
        }
      })
  }
  const removeHambOpen = () => {
    if (document.getElementById('nav-icone').classList.contains('open')) {
      document.getElementById('nav-icone').classList.remove('open')
    }
    if (document.getElementById('dropdown-menu').classList.contains('is-block')) {
      document.getElementById('dropdown-menu').classList.remove('is-block')
    }
  }

  const postVerifyRoute = () => {
    let buttonTry = document.querySelector('.button-width-try')
    buttonTry.classList.add('is-loading')
    window.axios.get(window.origin + `/services/${store.getState().id}`)
      .then((response) => {
        if (response.status == 200) {
          window.location.reload()
        }
      })
      .catch((error) => {
        if (error.response != undefined) {
          if (error.response.status != undefined && error.response.status == '403') {
            toast.error("A reunião " + store.getState().id + " ainda nao foi criada", { autoClose: 1000 })
          }
        }
      })
      .finally(() => {
        buttonTry.classList.remove('is-loading')
      })
  }

  return (
    <AnimatedPage>
      <ToastContainer />
      <Header />
      <input type="text" id='input-escond' />
      <div className='hug-icone'>
        <div id="nav-icone" onClick={(e) => toggleActivy(e)}>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>

      <div className="dropdown-menu" id="dropdown-menu" role="menu">
        <div className="dropdown-content">
          <a href="#" className="dropdown-item">
            {store.getState().email == undefined ? "Logado como convidado" : store.getState().email}
          </a>
          {!showButtons && <a onClick={(e) => logoutCall(e)} href="#" className="dropdown-item">
            Sair
          </a>}
          {showButtons && <a onClick={(e) => enterToVis(e)} href="#" className="dropdown-item">
            Entrar
          </a>}
        </div>
      </div>

      <div className='hug-notcreate-room' onClick={() => removeHambOpen()}>
        <div className='notcreate-conteudo'>
          <div className='text-error'>
            Seu anfitrião ainda não entrou na sala. Por favor, volte em alguns minutos.
          </div>

          <div className='buttons buttons-div'>
            <button className='button is-large button-color-primary is-info button-width-try' onClick={() => postVerifyRoute()}>Tente novamente</button>
         
          </div>
        </div>
        <div className='notcreate-conteudo-right'>
          <img src={citexLogo} className='img-call-create'></img>
       
        </div>
      </div>



      <div className="modal-table container-modal container-modal-bulma" id="container-modal">
        <div id="image-modal" className="modal">
          <div className="modal-background z-index-modal" onClick={(e) => enterToVis(e)} ></div>
          <div className="modal-content" >
            <div className="modal-card">
              <section className="modal-card-body scroll-personalize">
                <form>
                  <InputComponent label="Email" type="email" placeholder="Digite seu email" id="email" icon="fa-envelope" have={false} />
                  <InputComponent label="Senha" type="password" placeholder="Digite sua senha" id="password" icon="fa-lock" have={true} />
                  <div className='buttons pt-4'>
                    <button className='button is-link button-color-primary' onClick={(e) => postFormModal(e)}>Entrar</button>
                  </div>
                </form>
              </section>
              <button id="modal-close" className="modal-close" onClick={(e) => enterToVis(e)}></button>
            </div>
          </div>
        </div>
      </div>

    </AnimatedPage>
  )
}

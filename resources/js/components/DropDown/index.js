import React, { useEffect } from 'react'
import "./DropDown.css"
import { toast, ToastContainer } from "react-toastify"
import { RESET_STORE, store, USER_LOGGED } from "../../store/store"

export default function DropDown({ isWhiteLabel }) {

  const copyUrl = (e) => {
    toast.success('Link copiado com sucesso', { autoClose: 2000 })
    let url_atual = window.location.href;
    let url = document.getElementById('input-escond');
    url.value = url_atual;
    url.select();
    document.execCommand('copy')
    setTimeout(escondDrop, 500)
  }

  const escondDrop = () => {
    if (!document.getElementById('dropdown-jitsi').classList.contains('is-hidden')) {
      document.getElementById('dropdown-jitsi').classList.add('is-hidden')
    }
    if (document.getElementById('nav-icon1').classList.contains('open')) {
      document.getElementById('nav-icon1').classList.remove('open')
    }
  }

  const toggleActivy = (e) => {
    document.getElementById('nav-icon1').classList.toggle('open')
    document.getElementById('dropdown-jitsi').classList.toggle('is-hidden')

    if (document.getElementById('nav-icon1').classList.contains('open')) {
      document.querySelector('.ul-mbl').classList.remove('is-hidden')
    } else {
      document.querySelector('.ul-mbl').classList.add('is-hidden')
    }
  }

  const logoutCall = () => {
    window.axios.post('/services/logout')
      .then((response) => {
        if (response.data.status) {
          store.dispatch({ type: RESET_STORE })
          store.dispatch({ type: USER_LOGGED, logged: false })
          window.location.href = window.origin + '/login'
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

  const enterToModal = (e) => {
    escondDrop()
    let modal = document.getElementById('image-modal')
    modal.classList.toggle('is-active')
  }

  return (
    <>
      <input type="text" id='input-escond' />
      <ToastContainer />

      <div className={`hug-icon ${isWhiteLabel ? 'dropdown-left' : null}`} >
        <div id="nav-icon1" onClick={(e) => toggleActivy(e)}>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>


      <div id="dropdown-jitsi" className={`is-hidden  ${isWhiteLabel ? 'dropdown-left-itens' : null}`} role="menu">
        <div className={` dropdown-content `}>
          <a href="#" className="dropdown-item"
            onClick={() => copyUrl()}>Copiar o link da reunião
          </a>
          {store.getState().email == undefined && <a onClick={(e) => enterToModal(e)} href="#" className="dropdown-item">
            Entrar
          </a>}
          <a onClick={(e) => enterToVis(e)} href="#" className={store.getState().email == undefined ? "dropdown-item" : "exit-default2"}>
            {store.getState().email == undefined ? "Logado como convidado" : store.getState().email}
          </a>
          <a className="dropdown-item exit-default" onClick={(e) => logoutCall(e)}>
            Sair
          </a>
        </div>
      </div>
    </>
  )
}

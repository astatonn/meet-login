import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import AnimatedPage from '../../components/AnimatedPage/AnimatedPage'
import { store, USER_LOGGED } from '../../store/store'
import Header from "../../components/Header"
import "./NotFound.css"

export default function NotFound() {

  const [showButton, setShowButton] = useState(false)
  const navigate = useNavigate();
  useEffect(() => {
    if (store.getState().email != undefined) {
      console.log('logado')
      setShowButton(true)
    } else {
      console.log('convidado')
      setShowButton(false)
    }
  }, [])

  const linkInitial = () => {
    navigate('/')
  }
  const linkToLogin = () => {
    store.dispatch({ type: USER_LOGGED, logged: false })
    navigate('/login')
  }


  return (
    <AnimatedPage>
      <Header />
      <div className="notfound-page">
        <div className='has-text-centered p-4 is-size-1'>Página não encontrada | 404 </div>
        {showButton && <button className='button is-link is-large button-center button-color-primary' onClick={() => linkInitial()}>Pagina Inicial</button>}
        {!showButton && <button className='button is-success is-large button-center button-color-primary' onClick={() => linkToLogin()}>Login</button>}
      </div>
    </AnimatedPage>
  )
}

import "./Login.css"
import React, { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import Header from '../../components/Header'
import bgPenso from "../../../../public/assets/images/logobg-penso.png"
import Carroussel from '../../components/Carroussel'
import bgEBMail from '../../../../public/assets/images/background.jpg'
import Form from '../../components/Form'
import AnimatedPage from "../../components/AnimatedPage/AnimatedPage"
import { store } from "../../store/store"
import citexLogo from "../../../../public/assets/images/citex.png"

export default function Login() {
  
  const navigate = useNavigate();
  const [showRoute, setShowRoute] = useState(false)

  useEffect(() => {
    if (store.getState().logged != undefined && store.getState().logged.logged) {
      console.log('logado')
      navigate('/');
    } else {
      console.log('deslogado')
      setShowRoute(true)
    }
  }, [])

  return (
    <AnimatedPage>
      {showRoute && <>
      <div className="login-form-page">
        <div className="main-left">
        
        <Header />
        <div className='container-all'>
          <div className='container-all_right'>
            <Form />
          </div>
        </div></div>
        <div className="main-right">
            <div className="right-container-logo">
              <img src={citexLogo} className='right-logo'></img>
            </div>
            <Carroussel />
        </div>
       
        
        </div>
      </>}
    </AnimatedPage>
  )
}

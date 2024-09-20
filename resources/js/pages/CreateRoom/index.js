import "./Createroom.css"
import "./DropDownCreateRoom.css"
import React, { useEffect, useState } from 'react'
import { useNavigate } from "react-router-dom"
import AnimatedPage from '../../components/AnimatedPage/AnimatedPage'
import { store, USER_LOGGED } from "../../store/store"
import Header from "../../components/Header"
import Clock from 'react-live-clock';
import { FiVideo } from "react-icons/fi"
import TableCall from '../../components/TableCall'
import callVoid from "../../../../public/assets/images/call_void.png"
import callVoidWhiteLabel from "../../../../public/assets/images/call_void_whitelabel.png"
import citexLogo from "../../../../public/assets/images/citex.png"

import InputComponent from '../../components/InputComponent'
import { captureData } from '../../helpersfunctions/helpersFunctions'
import { ToastContainer, toast } from 'react-toastify';

export default function CreateRoom() {

    const navigate = useNavigate();
    const [showRoute, setShowRoute] = useState(false)
    const [dataCall, setDataCall] = useState('')
    const [showButtons, setShowButtons] = useState(false)
    const [modal, setModal] = useState(false)
    const [isWhiteLabel, setIsWhiteLabel] = useState(false)
    const [isLoaded, setIsLoaded] = useState(false);

    useEffect(() => {

        console.log('carrregou', whiteLabel)

        if (whiteLabel) {
            setIsWhiteLabel(true)
        }
        if (store.getState().logged != undefined && store.getState().logged.logged) {
            setShowRoute(true)
        } else {
            navigate('/login');
            setShowRoute(false)
        }

        if (store.getState().logged != undefined && store.getState().email != undefined) {
            console.log(store.getState().email)
            setShowButtons(false)
        } else {
            console.log('no email')
            setShowButtons(true)
        }

        window.axios.get(window.origin + `/services/get-user-rooms`)
            .then((response) => {
                functionThenDataTableCall(response)
            })
            .catch((error) => {
                console.log(error.response)
            })

    }, [modal])

    const randomId = () => {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    }

    const redirectToApiJitsi = (e) => {
        let id = `${randomId()}${randomId()}-${randomId()}${randomId()}-${randomId()}${randomId()}`
        navigate(`/${id}`)
    }

    const functionThenDataTableCall = (response) => {
        console.log(response)
        setDataCall(response.data.data)
    }

    const enterCaptureInputCall = (e) => {

        let callId = document.getElementById('input-call').value
        let inputCall = document.getElementById('input-call')

        if (callId == '') {
            inputCall.classList.add('is-danger')
            inputCall.parentElement.parentElement.lastChild.innerHTML = "Esse campo não pode ser vazio."
        }
        if (callId != '') {
            let captureButtonEnter = document.querySelector('.send-post-button')
            captureButtonEnter.classList.add('is-loading')

            window.axios.get(window.origin + `/services/${callId}`)
                .then((response) => {
                    if (response.status == 200) {
                        if (inputCall.classList.contains('is-danger')) {
                            inputCall.parentElement.parentElement.lastChild.innerHTML = ""
                            inputCall.classList.remove('is-danger')
                        }
                        navigate(`/${callId}`)
                    }
                })
                .catch((error) => {
                    if (error.response != undefined) {
                        if (error.response.status != undefined && error.response.status == '403') {
                            let inputCallForBidden = document.getElementById('input-call')
                            inputCallForBidden.parentElement.parentElement.lastChild.innerHTML = "Não foi possivel encontrar a reunião, verifique se o ID. está correto"
                        }

                        // if (error.response.status == '401') {
                        //     console.log('cai aqui')
                        // }
                    }
                })
                .finally(() => {
                    captureButtonEnter.classList.remove('is-loading')
                })
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
                    toast.success(response.data.message, { autoClose: 2000 })
                    document.getElementById('password').classList.add('is-success')
                    document.getElementById('email').classList.add('is-success')
                    setTimeout(redirect, 1500)
                    setModal(!modal)
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
    const redirect = (pathname) => {
        navigate(pathname == undefined ? '/' : pathname)
        enterToVis();
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
    const toggleActivy = (e) => {
        document.getElementById('nav-icone').classList.toggle('open')

        if (document.getElementById('nav-icone').classList.contains('open')) {
            document.getElementById('dropdown-menu').classList.add('is-block')
        } else {
            document.querySelector('#dropdown-menu').classList.remove('is-block')
        }
    }
    const removeHambOpen = () => {
        if (document.getElementById('nav-icone').classList.contains('open')) {
            document.getElementById('nav-icone').classList.remove('open')
        }
        if (document.getElementById('dropdown-menu').classList.contains('is-block')) {
            document.getElementById('dropdown-menu').classList.remove('is-block')
        }
    }

    useEffect(() => {
        setIsLoaded(true);
    }, []);

    return (
        <AnimatedPage>
            <ToastContainer />
            {showRoute && <>
                <>
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
                </>
                <div className="main-content">
                    <Header className='create-room-header'/>
                    <div className='create-room' onClick={() => removeHambOpen()}>


                        <div className='create-room_left'>
                            <h1 className='h1 mb-4'>Olá {store.getState().email == undefined ? '' : store.getState().email}! <br />Seja bem vindo<span className="hidden-whitelabel"> à <span className='has-text-weight-bold'>Z Reu</span> <span style={{color: '#6a7d00'}}>Web</span></span>.</h1>
                            {!showButtons && <button className='button is-link button-color-primary is-large button-call mt-4' onClick={(e) => redirectToApiJitsi(e)}>Nova reunião <FiVideo className="ml-2 mt-1" /></button>}
                            <h1 className='is-size-3 mt-6'>Entrar em uma reunião</h1>
                            <div className="field">
                                <div className="control has-icons-left has-icons-right is-flex">
                                    <input className="input is-medium input-border-bottom" type="email" placeholder="Digite o id da reunião" id="input-call" />
                                    <span className="icon is-medium is-left">
                                        <i className="fas fa-qrcode"></i>
                                    </span>
                                    <button className='button is-success is-medium send-post-button button-color-primary' onClick={(e) => enterCaptureInputCall(e)}>Entrar</button>
                                </div>
                                <p className="is-size-5 help-height has-text-danger "></p>
                            </div>

                        </div>
                        <div className='create-room_right'>
                            {dataCall.length == 0 ?
                                <img src={isWhiteLabel ? callVoidWhiteLabel : citexLogo}  className={`img-history-call create-room-right-logo ${isLoaded ? 'fade-in' : ''}`}/>
                                : <TableCall data={dataCall} />}
                        </div>
                    </div>
                </div>
            </>}


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


        </AnimatedPage >
    )
}

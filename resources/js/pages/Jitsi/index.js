import React, { useEffect, useState } from 'react'
import AnimatedPage from '../../components/AnimatedPage/AnimatedPage'
import { JitsiMeeting } from '@jitsi/react-sdk';
import "./Jitsi.css"
import DropDown from '../../components/DropDown';
import { useParams, useNavigate } from 'react-router-dom';
import InputComponent from "../../components/InputComponent"
import { captureData } from '../../helpersfunctions/helpersFunctions';
import { store, USER_LOGGED, USER_ID } from "../../store/store"
import { toast, ToastContainer } from "react-toastify"

export default function JitsiPage() {
    const [showJitsi, setShowJitsi] = useState(false)
    const [token, setToken] = useState(false)
    const [reload, setReload] = useState(false)
    const [user, setUser] = useState(false)
    const [destinationUrl, setDestinationUrl] = useState('')
    const [positionDropDown, setPositionDropDown] = useState(false)

    const { id } = useParams();
    const navigate = useNavigate();

    useEffect(() => {
        window.axios.get(window.origin + `/services/${id}`)
            .then((response) => {
                setPositionDropDown(response.data.server == "videoconferencia.email.com.br")
                setDestinationUrl(response.data.server);
                setToken(response.data.jwt);
                setUser(response.data.user);
                setShowJitsi(true)
            })
            .catch((error) => {
                if (error.response != undefined) {
                    if (error.response.status == '403') {
                        setShowJitsi(false)
                        store.dispatch({ type: USER_ID, id: id })
                        navigate(`/${id}`)
                    }
                    if (error.response.status != undefined && error.response.status == '401') {
                        navigate('/login')
                    }
                }
            })

    }, [reload])

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
                if (response.data.status) {
                    store.dispatch({ type: USER_LOGGED, logged: true, email: document.getElementById('email').value })
                    toast.success(response.data.message, { autoClose: 2000 })
                    document.getElementById('password').classList.add('is-success')
                    document.getElementById('email').classList.add('is-success')
                    setReload(!reload)
                    setTimeout(enterToVis, 1000)
                }
            })
            .catch((error) => {
                if (error.response != undefined) {
                    if (error.response.status != undefined && error.response.status == '422') {
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

    return (
        <AnimatedPage>
            <ToastContainer />
            {showJitsi && <div className='hug_jitsi'>
                <DropDown isWhiteLabel={positionDropDown} />
                <JitsiMeeting
                    configOverwrite={{
                        startWithAudioMuted: false,
                        startWithVideoMuted: false,
                    }}
                    roomName={id}
                    domain={destinationUrl}
                    getIFrameRef={node => node.style.height = '100vh'}
                    jwt={token}
                    onApiReady={(externalApi, event) => {
                        if (user) {
                            externalApi.executeCommand('displayName', user.name)
                        }
                        // if (!window.localStorage.getItem('meet-local-username')) {
                        //     if (user) {
                        //         externalApi.executeCommand('displayName', user.name)
                        //     }
                        // } else {
                        //     externalApi.executeCommand('displayName', window.localStorage.getItem('meet-local-username'))
                        // }
                        // externalApi.addEventListener('participantRoleChanged', function (event) {
                        //     window.localStorage.setItem('meet-local-username', externalApi.getDisplayName('local'))
                        //     //     if(event.role === 'moderator') { 
                        //     //         externalApi.executeCommand('toggleLobby', true);
                        //     //     }  
                        // });
                        externalApi.addEventListener('readyToClose', function (event) {
                            navigate('/')
                        });
                    }}

                />
            </div>}

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
                                        <button className='button is-link' onClick={(e) => postFormModal(e)}>Entrar</button>
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

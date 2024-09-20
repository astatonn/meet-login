import React from 'react'
import { captureData } from '../../helpersfunctions/helpersFunctions'
import InputComponent from '../InputComponent';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { useNavigate } from "react-router-dom";
import './Form.css';
import { store, USER_LOGGED } from '../../store/store';

export default function Form() {

    const axios = require('axios').default;
    const navigate = useNavigate();

    const postEnter = (e) => {
        e.preventDefault();
        let buttonEnter = e.target;
        buttonEnter.classList.add('is-loading');
        let captureForm = document.querySelector('.container-right_form')
        let errorInFormRemove = captureForm.querySelectorAll('.input')
        errorInFormRemove.forEach(k => {
            k.classList.remove('is-danger')
            k.parentElement.nextElementSibling.innerHTML = ""
        })

        let captureInputs = captureData();
        axios.post(window.origin + '/services/login', captureInputs)
            .then((response) => {
                if (response.data.status) {
                    toast.success(response.data.message, { autoClose: 2000 })
                    store.dispatch({ type: USER_LOGGED, logged: true, email: document.getElementById('email').value })
                    document.getElementById('password').classList.add('is-success')
                    document.getElementById('email').classList.add('is-success')
                    setTimeout(() => {
                        console.log('redirect')
                        redirect(response.data.intended)
                    }, 1500)
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
                buttonEnter.classList.remove('is-loading');
            })
    }

    const postVis = (e) => {
        e.preventDefault();
        let buttonVis = e.target;
        buttonVis.classList.add('is-loading')
        let obj = {}
        obj['isGuest'] = true;
        axios.post(window.origin + '/services/login', obj)
            .then((response) => {
                if (response.data.status) {
                    store.dispatch({ type: USER_LOGGED, logged: true })
                    toast.success('Seja bem vindo', { autoClose: 2000 })
                    setTimeout(() => {
                        redirect(response.data.intended)
                    }, 1500)
                }
            })
            .catch((error) => {
                console.log(error.response)
            })
            .finally(() => {
                buttonVis.classList.remove('is-loading')
            })
    }

    const redirect = (pathname) => {
        navigate(pathname == undefined ? '/' : pathname)
    }

    return (
        <div className='p-4 has-background-white'>
            <h2 className='is-size-4'>Realize conferências online e aproveite a tecnologia para fortalecer a colaboração e a tomada de decisões estratégicas<span className="hidden-whitelabel"> com <span className='has-text-weight-bold'>Z Reu</span> <span style={{color: '#6a7d00'}}>Web</span></span></h2>
            <form className='container-right_form pt-5'>
                <ToastContainer />

                <InputComponent label="Email" type="email" placeholder="Digite seu email" id="email" icon="fa-envelope" have={false} />
                <InputComponent label="Senha" type="password" placeholder="Digite sua senha" id="password" icon="fa-lock" have={true} />


                <div className="buttons pt-5">
                    <button className="button is-link button-color-primary" onClick={(e) => postEnter(e)}>Entrar</button>
                    <button className="button is-success button-color-secondary" onClick={(e) => postVis(e)}>Continuar como visitante</button>
                </div>
            </form>
        </div>
    )
}

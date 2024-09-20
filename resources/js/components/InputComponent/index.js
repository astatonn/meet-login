import React from 'react'
import "./InputComponent.css"
import { togglePass } from '../../helpersfunctions/helpersFunctions'

export default function InputComponent({ label, type, placeholder, id, icon, have }) {
    return (
        <div className="field">
            <label className="label">{label}</label>
            <div className="control has-icons-left has-icons-right">
                <input className="input input-border-bottom"  id={id} type={type} placeholder={placeholder} autoComplete="on" />
                <span className="icon is-small is-left">
                    <i className={"fas " + icon} ></i>
                </span>
                {have ? <button type="button" id="togglePass" className='button-hide-pass opacity-eye' onClick={() => togglePass()}></button> : <span className="icon is-small is-right">
                    <i className="fas fa-exclamation-triangle"></i>
                </span>}
            </div>
            <p className="help is-danger height-10"> </p>
        </div >
    )
}

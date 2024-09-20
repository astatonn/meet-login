import "./Header.css"
import React from 'react'
import logoNav from "../../../../public/assets/images/penso_meet_nav.png"
import logoNavWhiteLabel from "../../../../public/assets/images/penso_meet_nav_whitelabel.png"
import logoEBmail from "../../../../public/assets/images/ebmail-logo.png"
import Clock from 'react-live-clock';


export default function Header() {

    const removeHambOpen = () => {
        if (document.getElementById('nav-icone').classList.contains('open')) {
            document.getElementById('nav-icone').classList.remove('open')
        }
        if (document.getElementById('dropdown-menu').classList.contains('is-block')) {
            document.getElementById('dropdown-menu').classList.remove('is-block')
        }
    }

    return (
        <div className="header" onClick={() => removeHambOpen()}>
            <img className="header-img hidden-whitelabel" src={logoEBmail} alt="Logo Webmail" />
            <Clock
                format={' HH:mm:ss, DD/MM/YY'}
                ticking={true}
                timezone={'America/Sao_Paulo'}
                className="clock-react"
            />
        </div>
    )
}

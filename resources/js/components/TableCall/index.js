import React from 'react'
import { useNavigate } from "react-router-dom"
import "./TableCall.css"

export default function TableCall({ data }) {

    const navigate = useNavigate();

    const onClickPhoneCall = (e) => {
        let idCallOld = e.target.parentElement.parentElement.firstChild
        let valueIdCallOld = idCallOld.getAttribute('value')
        navigate(`/${valueIdCallOld}`)
    }

    return (
        <div className='hug-list'>
            <h1 className='is-size-3 history-call pb-4'>Histórico de reuniões</h1>
            <div className='table-call'>
                <table className='table-container table-wrapper table is-fullwidth is-striped is-hoverable is-fullwidth is-narrow '>
                    <thead>
                        <tr>
                            <th>Id da reunião</th>
                            <th>Criado em</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        {Object.keys(data).map(key => {
                            return <tr key={key}>
                                <td value={data[key].room_id}>{data[key].room_id}</td>
                                <td>{data[key].created_at}</td>
                                <td onClick={(e) => onClickPhoneCall(e)} ><button className='button is-info mx-4 my-1 button-call-table'>Entrar</button></td>
                            </tr>
                        }
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    )
}

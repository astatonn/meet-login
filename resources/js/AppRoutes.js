import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import CreateRoom from './pages/CreateRoom';
import Login from './pages/Login';
import NotFound from './pages/NotFound';
import 'bulma/css/bulma.min.css';
import "./AppRoutes.css"
import { Provider } from 'react-redux';
import { store, persistor } from './store/store';
import { PersistGate } from 'redux-persist/integration/react'
import JitsiPage from './pages/Jitsi';
import NotCreateRoom from "./pages/NotCreateRoom"

export default function AppRoutes() {

    const [userId, setUserId] = useState('penso')

    useEffect(() => {
        store.subscribe(() => {
            if (store.getState().id != undefined) {
                console.log(store.getState().id)
                setUserId(store.getState().id)
            }
        })
    }, [])

    return (
        <Provider store={store}>
            <PersistGate loading={null} persistor={persistor}>
                <Router>
                    <Routes>
                        <Route exact path="*" element={<NotFound />} />
                        <Route exact path="/" element={<CreateRoom />} />
                        <Route exact path="/login" element={<Login />} />
                        <Route exact path="/:id" element={<JitsiPage />} />
                        <Route exact path={`/${userId}`} element={<NotCreateRoom />} />
                    </Routes>
                </Router>
            </PersistGate>
        </Provider>
    )
}
if (document.getElementById('root')) {
    ReactDOM.render(<AppRoutes />, document.getElementById('root'));
}
import { createStore } from "redux";
import { persistStore, persistReducer } from 'redux-persist'
import storage from 'redux-persist/lib/storage'

export const USER_LOGGED = 'USER_LOGGED';
export const USER_ID = 'USER_ID';
export const RESET_STORE = 'RESET_STORE';

const initialState = '';
const reducers = (state = '', action) => {
    switch (action.type) {
        case USER_LOGGED:
            return {
                ...state,
                logged: action,
                email: action.email
            }

        case USER_ID:
            return {
                ...state,
                id: action.id
            }

        case RESET_STORE:
            return initialState;

        default:
            return state;
    }
}

const persistConfig = {
    key: 'meet',
    storage
};


const persistedReducer = persistReducer(persistConfig, reducers)

const store = createStore(persistedReducer, window.__REDUX_DEVTOOLS_EXTENSION__ && window.__REDUX_DEVTOOLS_EXTENSION__());
const persistor = persistStore(store)
export { store, persistor };


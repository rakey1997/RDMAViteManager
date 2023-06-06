export default {
    userid: (state) => state.app.userid,
    role: (state) => state.app.role,
    api_token: (state) => state.app.api_token,
    siderType: (state) => state.app.siderType,
    lang: (state) => state.app.lang,
    hostName: (state) => state.app.hostname,
    hostCardList: (state) => state.app.cardname,
    hostRdmaList: (state) => state.app.rdmaname,
    cardRdmaList: (state) => state.app.cardRdmaName,
    testHostPair: (state) => state.app.testHostPair,
    testForm: (state) => state.app.testForm,
};

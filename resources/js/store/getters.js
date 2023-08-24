export default {
    userid: (state) => state.app.userid,
    role: (state) => state.app.role,
    api_token: (state) => state.app.api_token,
    siderType: (state) => state.app.siderType,
    lang: (state) => state.app.lang,
    hostName: (state) => state.app.hostname,
    hostRdmaList: (state) => state.app.rdmaname,
    cardRdmaList: (state) => state.app.cardRdmaName,
    testUrl: (state) => state.app.testUrl,
    testHostPair: (state) => state.app.testHostPair,
};

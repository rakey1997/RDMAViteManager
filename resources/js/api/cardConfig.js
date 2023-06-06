import request from "./request";

export const getCard = (params) => {
    return request({
        url: "/card",
        params,
    });
};

export const excuteCmdFromSSH = (data) => {
    return request({
        url: "/exec_cmd",
        method: "post",
        data,
    });
};

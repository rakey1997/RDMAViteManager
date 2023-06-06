import request from "./request";

export const login = (data) => {
    return request({
        url: "/UserVerify",
        method: "POST",
        data,
    });
};

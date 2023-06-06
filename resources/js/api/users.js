import request from "./request";

export const getUsers = (params) => {
    return request({
        url: "/users",
        params,
    });
};

export const changeUserState = (uid, type) => {
    return request({
        url: `/users/${uid}/state/${type}`,
        method: "put",
    });
};

export const addUser = (data) => {
    return request({
        url: "/users",
        method: "post",
        data,
    });
};

export const editUser = (data) => {
    return request({
        url: `/users/${data.id}`,
        method: "put",
        data,
    });
};

export const deleteUser = (ids) => {
    return request({
        url: `/users/${ids}`,
        method: "delete",
    });
};

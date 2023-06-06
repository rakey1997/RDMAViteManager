import request from "./request";

export const getRdma = (params) => {
    return request({
        url: "/rdma",
        params,
    });
};

export const delRdma = (id) => {
    return request({
        url: `/rdma/${id}`,
        method: "delete",
    });
};

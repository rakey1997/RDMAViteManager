// const dayjs = require("dayjs");

// const filterTimes = (val, format = "YYYY-MM-DD") => {
//     if (!isNull(val)) {
//         val = parseInt(val) * 1000;
//         return dayjs(val).format(format);
//     } else {
//         return "--";
//     }
// };

export const isNull = (date) => {
    if (!date) return true;
    if (JSON.stringify(date) === "{}") return true;
    if (JSON.stringify(date) === "[]") return true;
};

function isObject(object) {
    return object != null && typeof object === "object";
}

/** 判断对象A是否是对象B的子集 */
export function isInclude(objA, objB) {
    /* 
  js delete删除对象的某个属性发现即使把当前对象重新赋值给临时变量后
  删除临时变量中的属性最终原对象的属性也会被删除的解决方法 
  */
    const tmpObj = JSON.stringify(objA);
    const smallObj = JSON.parse(tmpObj);
    const bigObj = JSON.parse(JSON.stringify(objB));

    for (let itemB in bigObj) {
        if (itemB === "state") {
            if (
                (objB[itemB] === true && objA[itemB] === 1) ||
                (objB[itemB] === false && objA[itemB] === 0)
            ) {
                delete smallObj[itemB];
            }
        }
        if (objB[itemB] === objA[itemB]) {
            delete smallObj[itemB];
        }
    }
    if (Object.keys(smallObj).length == 0) {
        return true;
    } else {
        return false;
    }
}

// export default (app) => {
//     app.config.globalProperties.$filters = {
//         filterTimes,
//     };
// };

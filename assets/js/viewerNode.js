function openNav() {
    document.getElementById("projectSidebar").style.width = "400px";
    document.getElementById("workplace").style.marginLeft = "400px";
}

function closeNav() {
    document.getElementById("projectSidebar").style.width = "0";
    document.getElementById("workplace").style.marginLeft = "0";
}

const NODE = 0;
const PROJECTS = 1;
const AUTH = 2;
const DATA = 3;

class ResponsePocket {
    OK = "OK";
    FAIL = "FAIL";

    DATA = "DATA";
    NODE = "NODE";
    PROJECT = "PROJECT";

    #header = null;
    #structData = null;
    #metaData = null;
    #valuesData = null;
    #keysMapData = null;
    #binaryData = null;

    constructor(pocket = null) {
        if (pocket) {
            this.#header = pocket.header;
            this.#structData = pocket.structData;
            this.#metaData = pocket.metaData;
            this.#valuesData = pocket.valuesData;
            this.#keysMapData = pocket.keysMapData;
            this.#binaryData = pocket.binaryData;
        }
    }

    setHeader(header) {
        if (header) {
            this.#header = header;
        }
    }

    getHeader() {
        return (this.#header) ? this.#header : null;
    }

    setNodeId(nodeId) {
        if (nodeId) {
            this.#metaData = {
                nodeId: nodeId
            }
        }
    }

    getNodeId() {
        return (this.#metaData.nodeId) ? this.#metaData.nodeId : null;
    }

    getPocket() {
        let pocketJson = {
            header: this.#header,
            structData: this.#structData,
            metaData: this.#metaData,
            valuesData: this.#valuesData,
            keysMapData: this.#keysMapData,
            binaryData: this.#binaryData
        };
        return JSON.stringify(pocketJson) + "\r\n\r\n";
    }

    setMetaData(metaData) {
        if (metaData) {
            this.#metaData = metaData;
        }
    }

    getMetaData() {
        return this.#metaData;
    }

    getFrame() {
        return this.#binaryData.frame;
    }

    getValuesData() {
        return this.#valuesData;
    }

    getKeysMapData() {
        return this.#keysMapData;
    }
}

class ResponseStates {
    #states;
    #currentStateIndex;

    constructor() {
        this.#states = [NODE,PROJECTS,AUTH,DATA];
        this.#currentStateIndex = 0;
    }

    current() {
        return this.#states[this.#currentStateIndex];
    }

    nextState() {
        this.#currentStateIndex = (this.#currentStateIndex + 1) % this.#states.length;
        return this.current();
    }

    toState(state) {
        this.#currentStateIndex = this.#states.indexOf(state);
        return this.current();
    }
}

var render = {
    nodeInput: function() {
        return `<form class="form-inline">
                  <div class="form-group mb-2">
                    <label for="readerNode"><b>Идентификатор считывающего узла:</b></label>
                  </div>
                  <div class="form-group mx-sm-3 mb-2">
                    <input type="text" name="readerNode" class="readerNode form-control" id="readerNode">
                  </div>
                  <input type="button" class="btn btn-secondary mb-2 btn-node" value="Выбрать считывающий узел">
                </form>`;
    },
    projectsList: function(projectsList) {
        let html = `<div class="list-group">`;
        for (let [id, name] of Object.entries(projectsList)) {
            html += `<div class="list-group-item" id="${id}">
                        <p>${name}</p>
                    </div>
                    <div class="list-group-item" id="${id}">
                        <p>${name}</p>
                    </div>`;
        }
        html += `</div>`;
        return html;
    },
    authProject: function() {
        return `<form>
              <div class="form-group">
                <label for="loginProject">Логин SCADA-проекта</label>
                <input type="text" class="form-control" id="loginProject">
              </div>
              <div class="form-group">
                <label for="passwordProject">Пароль SCADA-проекта</label>
                <input type="password" class="form-control" id="passwordProject">
              </div>
              <input type="button" class="btn btn-secondary btn-auth" value="Подтвердить">
            </form>`;
    },
    scadaProjectInfo: function (metaData) {
        return `<p>SCADA-система: <span style="color: sandybrown">${metaData.scadaNameData}</span></p>
                <p>SCADA-проект: <span style="color: sandybrown">${metaData.sprojectNameData}</span></p>
                <p>Время обновления данных: <span style="color: sandybrown">${metaData.timestampData}</span></p>`;
    },
    scadaProjectStruct: function (valuesData,keysMapData) {
        let objectsModel = {};
        for (let [objectId,objectStructString] of Object.entries(keysMapData)) {
            let objectStruct = objectStructString.split(".");
            decomposeObjectStruct(objectsModel,objectStruct,objectId);
        }
        return "Объектная структура проекта: <ul>" + buildStructHtml(objectsModel) + "</ul>";

        function decomposeObjectStruct(objectsModel,objectStruct,objectId) {
            let objectName = objectStruct.shift();
            if ($.isEmptyObject(objectsModel[objectName])) {
                objectsModel[objectName] = {
                    id: null,
                    value: "",
                    children: {}
                }
            }
            if (objectStruct.length === 0) {
                objectsModel[objectName].id = objectId;
                if (typeof valuesData[objectId] !== "undefined" &&
                    typeof valuesData[objectId][0].value !== "undefined") {
                    objectsModel[objectName].value = valuesData[objectId][0].value; //под индексом 0 лежат самые свежие данные
                }
            } else {
                objectsModel[objectName].children = decomposeObjectStruct(objectsModel[objectName].children,objectStruct,objectId);
            }
            return objectsModel;
        }

        function buildStructHtml(objectsModel) {
            let html = "";
            for (let [objectName,objectData] of Object.entries(objectsModel)) {
                html += `<li>${objectName} <span class="object-value" data-value="${objectData.id}">${objectData.value}</span>`;
                if (!$.isEmptyObject(objectData.children)) {
                    html += "<ul>" + buildStructHtml(objectData.children) + "</ul>";
                }
                html += `</li>`;
            }
            return html;
        }
    },
    failConnection: function () {
        return `<h5>Не удалось подключиться к серверу</h5>
                <button type="button" class="btn btn-secondary btn-lg btn-block" onclick="setTimeout(function() {
          window.location.reload();
        },500)" data-dismiss="modal">Переподключиться</button>`;
    }
}

var isBusy = false;
var response = null;
var readerNodeId = null;
var modal = $('#viewerNodeModal');
var responseStates = new ResponseStates();
var conn = new WebSocket('ws://localhost:' + port);
var authData = {
    login: "",
    password: "",
    isAuth: false
}

conn.onopen = function(e) {
    console.log("Соединение установлено!");
};

conn.onmessage = function(e) {
    response = JSON.parse(e.data);
    handleResponse(response);
};

conn.onclose = function (e) {
    console.log(e);
    modal.find(".modal-body").html(render.failConnection());
    modal.modal("show");
}

conn.onerror = function (e) {
    console.log(e);
    modal.find(".modal-body").html(render.failConnection());
    modal.modal("show");
}

function handleResponse(response) {
    let pocket = new ResponsePocket(response);
    console.log(pocket);
    switch (responseStates.current()) {
        case NODE:
            if (pocket.getHeader() === pocket.OK) {
                modal.find(".modal-body").html(render.nodeInput());
                modal.modal("show");
            } else if (pocket.getHeader() === pocket.FAIL) {
                alert("Не удалось подключиться к серверу интеграции!");
            }
            break;
        case PROJECTS:
            if (pocket.getHeader() === pocket.OK) {
                modal.find(".modal-body").html(render.projectsList(pocket.getMetaData()));
            } else if (pocket.getHeader() === pocket.FAIL) {
                resetState("Не удалось получить список SCADA-проектов!");
            }
            break;
        case AUTH:
            if (pocket.getHeader() === pocket.OK) {
                modal.find(".modal-body").html(render.authProject());
            } else
            if (pocket.getHeader() === pocket.DATA) {
                let metaData = pocket.getMetaData();
                if (metaData !== undefined) {
                    authData.login = metaData.loginData;
                    authData.password = metaData.passwordData;
                } else {
                    resetState("Не удалось получить данные SCADA-проекта!");
                }
            } else
            if (pocket.getHeader() === pocket.FAIL)  {
                resetState("Не удалось получить данные SCADA-проекта!");
            }
            break;
        case DATA:
            if (pocket.getHeader() === pocket.DATA && authData.isAuth && !isBusy) {
                isBusy = true;
                let frame = pocket.getFrame();
                if (frame !== undefined) {
                    $(".frame").html('<img class="w-100 h-100" src="data:image/jpg;base64,' + frame + '"/>');
                }
                let valuesData = pocket.getValuesData(),
                    keysMapData = pocket.getKeysMapData();
                if (valuesData !== undefined && keysMapData !== undefined) {
                    $(".scada-project-info").html(render.scadaProjectInfo(pocket.getMetaData()));
                    $(".scada-project-struct").html(render.scadaProjectStruct(valuesData,keysMapData));
                }
                isBusy = false;
                break;
            } else
            if (pocket.getHeader() === pocket.FAIL)  {
                resetState("Не удалось получить данные SCADA-проекта!");
            }
    }
}

function resetState(msg = "Ошибка получения данных сервера!") {
    responseStates.toState(NODE);
    let pocket = {header: "OK"};
    handleResponse(pocket);
    alert(msg);
}

$(document).on("click", ".modal-body .btn-node", function() {
    readerNodeId = $(this).siblings("input").val();
    let pocketData = new ResponsePocket();
    pocketData.setHeader(pocketData.NODE);
    pocketData.setNodeId(readerNodeId);
    conn.send(pocketData.getPocket());
    responseStates.nextState();
});

$(document).on("click", ".modal-body .list-group-item", function() {
    let sProjectId = $(this).attr("id");
    let metaData = {
        nodeId: readerNodeId,
        sprojectIdData: sProjectId
    }
    let pocketData = new ResponsePocket();
    pocketData.setHeader(pocketData.PROJECT);
    pocketData.setMetaData(metaData);
    conn.send(pocketData.getPocket());
    responseStates.nextState();
});

$(document).on("click", ".modal-body .btn-auth", function() {
    let login = $(".modal-body #loginProject").val();
    let password = $(".modal-body #passwordProject").val();
    if (authData.login === login && authData.password === password) {
        modal.modal("hide");
        authData.isAuth = true;
        responseStates.nextState();
    } else {
        alert("Не удалось авторизовать SCADA-проект");
    }
});


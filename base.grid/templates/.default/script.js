BX.namespace('Aclips.Base.List')

BX.Aclips.Base.List = {
    gridId: null,
    signedParameters: null,

    init: function (params) {
        this.gridId = params.gridId;
        this.signedParameters = params.signedParameters;
    },

    baseAction: function (userName) {
        alert(userName);
    },

    baseGroupAction: function () {
        alert("Выбраны элементы: " + this.getSelectedIDs());
    },

    getSelectedIDs: function () {
        let grid = this.getGridInstance();
        if (grid) {
            return grid.getRows().getSelectedIds();
        }
    },

    getGridInstance: function () {
        let grid = BX.Main.gridManager.getById(this.gridId);
        return grid.instance;
    },
}

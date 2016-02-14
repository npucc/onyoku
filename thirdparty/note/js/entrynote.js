var noteform = new Ext.form.FormPanel({
    baseCls: 'x-plain'
    ,labelWidth: 75
    ,url:'save-note.php'
    ,defaultType: 'textfield'
    ,items: [{
        fieldLabel: 'お名前'
		,id : 'noteInputName'
        ,name: 'noteInputName'
        ,anchor:'50%'
    }, {
        fieldLabel: 'チケットNo'
		,id: 'noteInputNo'
        ,name: 'noteInputNo'
        ,anchor:'30%'
    }, {
        xtype: 'textarea'
        ,hideLabel: true
		,id: 'noteInputMsg'
        ,name: 'noteInputMsg'
        ,anchor: '100% -53' 
    }]
});

var notewindow = new Ext.Window({
    title: '思い出ノート'
    ,width: 500
    ,height:300
    ,minWidth: 300
    ,minHeight: 200
    ,layout: 'fit'
    ,plain:true
    ,bodyStyle:'padding:5px;'
    ,buttonAlign:'center'
    ,items: noteform
	,closeAction: 'hide'
    ,buttons: [{
        text: '書込み'
		,handler:writeNote
    },{
        text: 'キャンセル'
		,handler:noteHide
    }]
});

function noteHide() {
	notewindow.hide();
}

function writeNote() {
	if (checkData()) {
			dd = new Date();
            Ext.Ajax.request({
            	waitMsg: '処理中...'
            	,url: './note.php'
            	,params: {
                	mode: "add"
                	,ticket:   Ext.get('noteInputNo').getValue()
                	,name: Ext.get('noteInputName').getValue()
					,message: Ext.get('noteInputMsg').getValue()
					,date: dd.format('Y/m/d')
            	}
            	,success: function(response){
                	var result=eval(response.responseText);
                	switch(result){
                    	case 1:
                        	Ext.MessageBox.alert('登録','登録しました.');
							noteHide();
//                        	refreshCategory();
//                        	clearCategoryInput();
                        	break;
                    	default:
                        	Ext.MessageBox.alert('警告','登録に失敗しました.');
                        	break;
                	}
            	}
            	,failure: function(response){
                	var result=response.responseText;
                	Ext.MessageBox.alert('エラー','ファイルが開けません');
            	}
			});
	} else {
        Ext.MessageBox.show({
			title:'警告'
			,msg:'設定に誤りがあります。'
			,buttons: Ext.MessageBox.OK
		});
	}
}
function checkData() {
   	var No = Ext.get('noteInputNo').getValue();
    var Name = Ext.get('noteInputName').getValue();
	var Msg =  Ext.get('noteInputMsg').getValue();

	if (No == null || Name == null || Msg == null) {
		return false;
	} else {
		return (parseInt(No) == No);
	}

}

Ext.onReady(function(){

	var dateSelectorStore = new Ext.data.Store({
		proxy: new Ext.data.HttpProxy({
			url: 'note.php',
			method: 'POST',
		}),
		baseParams: { mode : "dirlist" },

		reader: new Ext.data.JsonReader({
			root: 'results', 
			totalProperty: 'total'
		},[
			{name: 'date'}
		])
	});
	dateSelectorStore.load();

	var dateSelector = new Ext.form.ComboBox({
   		store: dateSelectorStore
		,triggerAction: 'all'
   		,valueField:'date'
   		,displayField:'date'
   		,editable: false
   		,mode: 'local'
		,width: 100
	});
    var record =  [
            {name: 'date', mapping: 'date'}
            ,{name: 'name', mapping: 'name'}
            ,{name: 'ticket', mapping: 'ticket'}
            ,{name: 'message', mapping: 'message'}

    ];


    var store = getStore(record);
    if (store.getCount()==0) store.load({});

    var tpl = new Ext.XTemplate(
        '<tpl for=".">'
        ,'<div class="item-wrap" style="border:1px solid #ddd; width:80%; word-wrap:break-word;">'
            ,'<div style="background-color:#EFEFA7; font-size:small">'
            ,'<h3>{date} {name}さん</h3>'
            ,'</div>'
            ,'<div style="background-color:#FFFFE5; font-size:small">'
            ,'<pre>{message}</pre>'
            ,'</div>'
        ,'</div>'
        ,'</tpl>');


    var resultsView = new Ext.DataView({
         tpl: tpl
        ,region: 'center'
        ,store: store
        ,itemSelector: 'div.item-wrap'
    });

	dateSelector.on('select',function() {
		var selectDay = dateSelector.getValue();
		store.load({params:{date:selectDay,method:'post'}});
		resultsView.refresh();
		
	});

	var showPanel = new Ext.Panel({
		applyTo: "container"
        ,width: 400
        ,height: 300
        ,autoScroll: true
        ,title: '思い出ノート'
        ,layout: 'border'
		,tbar: [
			'月選択：',dateSelector
			,'-',{
				text:'登録'
				,handler:showEntry
			}
		]
        ,items: resultsView
    })
});

function showEntry() {
    if(!notewindow.isVisible()){
        notewindow.setTitle('思い出ノート書き込み');
        notewindow.show();
     } else {
        notewindow.toFront();
     }
}

function getStore(record){
	var store = new Ext.data.JsonStore({
        fields: record,
        root: 'results',
        url: './note.php'
    });
    return store;
}

notewindow.on('hide',function() { 
	resultsView.refresh();
		});


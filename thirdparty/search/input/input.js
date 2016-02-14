Ext.onReady(function(){
	Ext.Updater.defaults.disableCaching = true;
	var body = Ext.getBody();

	function newInputHelperStore() {
		var arg = null;
		var meta = {root: 'root', totalProperty: 'results'};
		var fields = [{name: 'name', type: 'string'}];

		if (newInputHelperStore.arguments.length)
			arg = newInputHelperStore.arguments[0];
		if ((arg == 'label')||(arg == 'artist'))
			fields.push({name: 'kana', type: 'string'});
		if ((arg == 'category')||(arg == 'artist')||
		    (arg == 'label')||(arg == 'music')||
			(arg == 'disc_type')) {
			meta.id = 'id';
			fields.push({name: 'id', type: 'int'});
		}
		if (arg == 'stocker')
			fields.push({name: 'caption', type: 'string'});

		return new Ext.data.Store({
			proxy: new Ext.data.HttpProxy({
				url: 'input_helper.php',
				method: 'POST',
				disableCaching : true
			}),
			reader: new Ext.data.JsonReader(
				meta,
				fields
			)
		});
	}

	var disc_type = new Ext.form.ComboBox({
		fieldLabel: 'ディスクタイプ',
		name: 'disc_type',
		typeAhead: false,
		store: newInputHelperStore('disc_type'),
		displayField: 'name',
		valueField: 'id',
		queryParam: 'disc_type',
		minChars: 1,
		forceSelection: true,
		triggerAction: 'all'
	});
	var disc_title = new Ext.form.TextField({
		fieldLabel: 'ディスク名',
		name: 'disc_title',
		anchor:'95%'
	});
	var disc_subtitle = new Ext.form.TextField({
		fieldLabel: 'サブタイトル（副題）',
		name: 'disc_subtitle',
		anchor:'95%'
	});
	var product_no = new Ext.form.TextField({
		fieldLabel: '製品番号',
		name: 'product_no',
		anchor:'95%'
	});
	var disc_year = new Ext.form.NumberField({
		fieldLabel: '発売年',
		name: 'disc_year',
		anchor:'95%'
	});
	var keyword = new Ext.form.TextField({
		fieldLabel: 'キーワード',
		name: 'keyword',
		anchor:'95%'
	});
	var stocker = new Ext.form.ComboBox({
		anchor:'95%',
		fieldLabel: '収蔵棚',
		name: 'stocker',
		typeAhead: false,
		store: newInputHelperStore('stocker'),
		displayField: 'name',
		queryParam: 'stocker',
		minChars: 1,
		triggerAction: 'all',
		listeners: {
			'select': function(c,r) {
				stocker_caption.setValue(r.get('caption'));
			}
		}
	});
	var stocker_caption = new Ext.form.TextField({
		fieldLabel: '収蔵棚の説明',
		name: 'stocker',
		anchor:'95%'
	});
	function makeLabelConfig(num) {
		return {
			anchor:'95%',
			fieldLabel: 'レーベル' + num,
			name: 'label' + num,
			typeAhead: false,
			store: newInputHelperStore('label'),
			displayField: 'name',
//		valueField: 'id',
			queryParam: 'label',
			minChars: 1,
			listeners: {
				'focus': function(c) {
					c.need_clear = false;
					c.selectflag = false;
				},
				'select': function(c,r) {
					c.label_kana.setValue(r.get('kana'));
					c.label_id.setValue(r.get('id'));
					c.selectflag = true;
					c.label_kana.focus(false,10);
				},
				'change': function(c) {
					if (!c.selectflag)
						c.need_clear = true;
				},
				'blur': function(c) {
					if (c.need_clear) {
						c.label_kana.setValue('');
						c.label_id.setValue('');
					}
				}
			}
		}
	}
	function newLabelKana(num) {
		return new Ext.form.TextField({
			fieldLabel: 'レーベル'+num+'カナ読み',
			name: 'label' + num,
			anchor:'95%',
			listeners: {
				'change': function() {
					this.c.label_id.setValue('');
				}
			}
		});
	}
	function newLabel(no) {
		var ret = new Ext.form.ComboBox(makeLabelConfig(no)); 
		ret.label_kana = newLabelKana(no);
		ret.label_kana.c = ret;
		ret.label_id = new Ext.form.Hidden({
			name: 'label' + no
		});
		return ret;
	}
	var label1 = newLabel(1);
	var label2 = newLabel(2);

	var description = new Ext.form.TextArea({
		fieldLabel: 'メモ',
		name: 'description',
		anchor:'95%'
	});
	var condition = new Ext.form.ComboBox({
		fieldLabel: '状態',
		name: 'condition',
		store: new Ext.data.Store({
			url: 'get_condition.php',
			reader: new Ext.data.JsonReader(
				{root: 'condition', totalProperty: 'results'},
				[
					{name: 'name', type: 'string'},
					{name: 'id', type: 'int'},
				]
			)
		}),
		displayField: 'name',
		valueField: 'id',
		triggerAction: 'all',
		editable: false
	});
	var musicstore = new Ext.data.SimpleStore({
		fields: [
			{name: 'side'},
			{name: 'track'},
			{name: 'title'},
			{name: 'id'},
			{name: 'year'},
			{name: 'artist'},
			{name: 'description'}
		]
	});

	var MusicDefault = Ext.data.Record.create([
		{name:'side',    type:'string'},
		{name:'track',   type:'int'},
		{name:'title',   type:'string'},
		{name:'id',      type:'int'},
		{name:'year',    type:'int'},
		{name:'artist',  type:'auto'},
		{name:'description', type:'string'}
	]);

	var ArtistDefault = Ext.data.Record.create([
			{name: 'artist',   type: 'string'},
			{name: 'kana',     type: 'string'},
			{name: 'id',       type: 'int'},
			{name: 'category', type: 'string'},
			{name: 'category_id', type: 'int'}
	]);

	var music_list_cm = new Ext.grid.ColumnModel([{
		header: '収録面',
		sortable: true,
		dataIndex: 'side',
		editor: new Ext.form.ComboBox({
			mode: 'local',
			allowBlank: false,
			editable: false,
			forceSelection: true,
			store: new Ext.data.SimpleStore({
				fields: ['name'],
				data: [['A'],['B']]
			}),
			displayField: 'name',
			triggerAction: 'all'
		})
	},{
		header: 'トラック番号',
		sortable: true,
		dataIndex: 'track',
		editor: new Ext.form.TextField({
			allowBlank: false
		})
	},{
		header: '曲名',
		dataIndex: 'title',
		editor: new Ext.form.ComboBox({
			allowBlank: false,
			typeAhead: false,
			minChars: 1,
			store: newInputHelperStore('music'),
			displayField: 'name',
			queryParam: 'music',
			listeners:{
				'select': function (c, r, i) {
					var id = r.get('id');
					var grid = music_list;
					var sm = grid.getSelectionModel();
					var rec = sm.getSelected();
					rec.set('id', id);
					c.selectflag = true;
				},
				'change': function (c, n, o) {
					if (c.selectflag) {
						return;
					}
					var grid = music_list;
					var sm = grid.getSelectionModel();
					var row = sm.getSelected();
					row.set('id', 0);
					c.selectflag = false;
				}
			}
		})
	},{
		header: '発表年',
		dataIndex: 'year',
		editor: new Ext.form.TextField({
			allowBlank: false
		})
	},{
		header: 'メモ',
		dataIndex: 'description',
		editor: new Ext.form.TextField({
			allowBlank: true
		})
	}]);
	var default_side = new Ext.form.ComboBox({
		width: 20,
		editable: false,
		allowBlank: false,
		hideTrigger: true,
		mode: 'local',
		store: new Ext.data.SimpleStore({
			fields: ['name'],
			data: [['A'],['B']]
		}),
		displayField: 'name',
		triggerAction: 'all',
		forceSelection: true,
		value: 'A',
		listeners: {
			'select': function(c, r, i) {
				if (typeof this.reverse_side == 'undefined') {
					this.reverse_side = 1;
				}
				if (this.previous != i){
					this.previous = i;
					var tmp = track_count.getValue();
					track_count.setValue(this.reverse_side);
					this.reverse_side = tmp;
				}
				return true;
			}
		}
	});

	var track_count = new Ext.form.NumberField({
		width: 20,
		allowBlank: false,
		value: 1
	});
	var default_year = new Ext.form.NumberField({
		width: 40,
		allowBlank: false,
		value: 0
	});
	var music_list_sm = new Ext.grid.RowSelectionModel({
		singleSelect: true,
		listeners: {
			'rowselect': update_artisttab
		}
	});
	var music_list = new Ext.grid.EditorGridPanel({
		store: musicstore,
		cm: music_list_cm,
		sm: music_list_sm,
		clicksToEdit: 1,
		stripeRows: true,
		frame: true,
		region: 'center',
		height: 360,
		title:'収録曲一覧',
		viewConfig: {
			forceFit: true
		},
		listeners: {
			'afteredit': music_list_afteredit,
			'beforeedit': select_before_edit
		},
		tbar: [{
			text: '追加',
			handler: function(){
				var artiststore = new Ext.data.SimpleStore({
					fields: [
						{name: 'artist'},
						{name: 'kana'},
						{name: 'id'},
						{name: 'category'},
						{name: 'category_id'}
					]
				});
				var track = track_count.getValue();
				var new_data = new MusicDefault({
					side: default_side.getValue(),
					track: track,
					title: '',
					id: 0,
					artist: artiststore,
					year: default_year.getValue(),
					description: ''
				});
				music_list.stopEditing();
				musicstore.add(new_data);
				var maintab = Ext.getCmp('maintab');
				var artisttab = Ext.getCmp('artisttab');
				if (!artisttab) {
					artist_list_config.store = artiststore;
					maintab.add({
						title: 'アーティスト（'
						 + default_side.getValue()
						 + '-' + String(track) + '）',
						layout:'border',
						defaults: {width: '100%'},
						border:false,
						id: 'artisttab',
						items:[
							new Ext.grid.EditorGridPanel(artist_list_config)
						]
					}).current_music = 0;
					maintab.doLayout();
				}
				var default_artists = Ext.getCmp('default_artist_list').getStore();
				default_artists.each(function (r){
					var a = new ArtistDefault({
						artist: r.get('artist'),
						kana: r.get('kana'),
						id: r.get('id'),
						category: r.get('category'),
						category_id: r.get('category_id')
					});
					artiststore.add(a);
					return true;
				});
				track_count.setValue(++track);
			}
		}, { xtype: 'tbseparator' }, {
			text: '削除',
			handler: function(){
				var record = music_list_sm.getSelected();
				musicstore.remove(record);
			}
		},
		{ xtype: 'tbseparator' },
		{ xtype: 'tbtext', text: 'デフォルトの収録面' },
		  default_side,
		{ xtype: 'tbseparator' },
		{ xtype: 'tbtext', text: '　　次のトラック番号' },
		  track_count,
		{ xtype: 'tbseparator' },
		{ xtype: 'tbtext', text: 'デフォルトの発表年'},
		  default_year
		]
	});

	function update_artistlist(r) {
		var artiststore = r.get('artist');
		var artist_list = Ext.getCmp('artist_list');
		var title = r.get('title');
		var artisttab = Ext.getCmp('artisttab');

		artist_list.reconfigure(artiststore, artist_list_cm);
		if (title == '')
			title = 'アーティスト';

		title = title + '（' + r.get('side')
		  + '-' + String(r.get('track')) + '）';
		artisttab.setTitle(title);
		artisttab.current_music = musicstore.indexOf(r);
	}
	function update_artisttab(o, rowIndex, r) {
		update_artistlist(r);
		return true;
	};
	function music_list_afteredit(e) {
		var title = e.record.get('title');
		var artisttab = Ext.getCmp('artisttab');

		if (title == '')
			title = 'アーティスト';

		title = title + '（' + e.record.get('side')
		  + '-' + String(e.record.get('track')) + '）';
		artisttab.setTitle(title);

		return true;
	};
	var artist_list_sm = new Ext.grid.RowSelectionModel({
		singleSelect: true
	});
	var category_store = newInputHelperStore('category');
	var artist_helper_store = newInputHelperStore('artist');

	var recent_artist = new Ext.data.SimpleStore({
		fields: [
			{name: 'name'},
			{name: 'kana'},
			{name: 'id'},
		]
	});
	var RecentArtistRecord = Ext.data.Record.create([
		{name: 'name',   type: 'string'},
		{name: 'kana',     type: 'string'},
		{name: 'id',       type: 'int'}
	]);

	function ArtistListColumnModel(list_name) {
		return new Ext.grid.ColumnModel([{
			header: 'アーティスト名',
			dataIndex: 'artist',
			editor: new Ext.form.ComboBox({
				name: 'artist_name',
				allowBlank: false,
				forceSelection: true,
				typeAhead: false,
				minChars: 1,
				store: artist_helper_store,
				displayField: 'name',
				queryParam: 'artist',
				listeners:{
					'select': function (c, r, i) {
						var kana = r.get('kana');
						var id = r.get('id');
						var name = r.get('name');
						var grid = Ext.getCmp(list_name);
						var sm = grid.getSelectionModel();
						var rec = sm.getSelected();
						rec.set('kana', kana);
						rec.set('id', id);
						var recent_rec = new RecentArtistRecord({
							'name' : name,
							'kana' : kana,
							'id'   : id
						});
						recent_artist.insert(0,recent_rec);
					},
					'beforequery':function(o){
						if (o.query == '') {
							o.combo.reset();
							artist_helper_store.removeAll();
							artist_helper_store.add(recent_artist.getRange());
							o.cancel = true;
							o.combo.expand();
						}
						else
							artist_helper_store.reload();
						return true;
					}
				}
			})
		},{
			header: 'カナ読み',
			dataIndex: 'kana',
			editor: new Ext.form.TextField({
				allowBlank: false,
				readOnly: true
			})
		},{
			header: '参加区分',
			dataIndex: 'category',
			editor: new Ext.form.ComboBox({
				allowBlank: false,
				typeAhead: false,
				minChars: 1,
				forceSelection: true,
				store: category_store,
				displayField: 'name',
				queryParam: 'category',
				lazyRender: true,
				triggerAction: 'all',
				listeners:{
				'select': function (c,r,i) {
						var id = r.get('id');
						var grid = Ext.getCmp(list_name);
						var sm = grid.getSelectionModel();
						var rec = sm.getSelected();
						rec.set('category_id', id);
				}}
			})
		}])
	}
	var artist_list_cm = ArtistListColumnModel('artist_list');

	function select_before_edit(o) {
		var grid = o.grid;
		var sm = grid.getSelectionModel();
		sm.selectRow(o.row);
	}
	function ArtistListConfig(list_name) {
		if (!list_name)
			list_name = 'artist_list';
		this.id = list_name;
		if (!list_name) {
			this.cm = artist_list_cm;
			this.sm = artist_list_sm;
		}
		else {
			this.cm = ArtistListColumnModel(list_name);
			this.store = new Ext.data.SimpleStore({
				fields: [
					{name: 'artist'},
					{name: 'kana'},
					{name: 'id'},
					{name: 'category'},
					{name: 'category_id'}
				]
			});
			this.sm = new Ext.grid.RowSelectionModel({
				singleSelect: true
			});
		}
		this.clicksToEdit = true;
		this.stripeRows = true;
		this.frame = true;
		this.region = 'center';
		this.height = 360;
		this.title = 'アーティスト一覧';
		this.viewConfig = {
			forceFit: true
		};
		this.listeners = {
			'beforeedit': select_before_edit
		};
		this.tbar = [{
			text: '追加',
			handler: function(){
				var new_data = new ArtistDefault({
					artist: '',
					kana: '',
					id: 0,
					category: '',
					category_id: 0
				});
				var artist_list = Ext.getCmp(list_name);
				var artist_store = artist_list.getStore();
				artist_list.stopEditing();
				artist_store.insert(0,new_data);
				artist_list.startEditing(0,0);
			}
		},{ xtype: 'tbseparator' },{
			text: '削除',
			scope: this,
			handler: function(){
				var record = this.sm.getSelected();
				var artist_list = Ext.getCmp(list_name);
				var artist_store = artist_list.getStore();

				artist_store.remove(record);
			}
		},{ xtype: 'tbseparator' },{
			text: '新規アーティスト登録',
			handler: function() {
				var width = 300;
				var height = 300;
				var artist_register = new Ext.FormPanel({
					labelAlign: 'top',
					title: '新規アーティスト登録',
					bodyStyle: 'padding:5px',
					width:width,
					height:height,
					hideBorders: true,
					url: 'private/input_artist.php',
					method: 'POST',
					items: [{
						layout:'form',
						border:false,
						items: [
							new Ext.form.ComboBox({
								name: 'artist_name',
								allowBlank: false,
								typeAhead: false,
								minChars: 1,
								store: newInputHelperStore('artist'),
								displayField: "name",
								queryParam: 'artist',
								fieldLabel: '名前'
							}),
							new Ext.form.TextField({
								name: 'artist_kana',
								allowBlank: false,
								fieldLabel: 'カナ読み'
							}),
							new Ext.form.TextArea({
								name: 'artist_description',
								allowBlank: true,
								width: width - 50,
								height: 80,
								fieldLabel: 'メモ'
							})
						],
						buttons: [
							{text:'save', scope:this, handler:save_artist}
						]
					}]
				});
				var win = new Ext.Window({
					width:width,
					height:height,
					items: artist_register
				});
				win.show();
				function save_artist() {
					artist_register.getForm().submit({
						success: function(form, action) {
							alert('the artist has been successfully registered');
							win.close();
						},
						failure: function(form, action) {
							alert('failed to register the artist');
						}
					});
				}
			}
		}];
	}
	
	var default_artist_list_config = new ArtistListConfig('default_artist_list');
	var default_artist_list = new Ext.grid.EditorGridPanel(default_artist_list_config);

	var artist_list_config = new ArtistListConfig();
	artist_list_config.tbar.push(
		{ xtype: 'tbseparator' },
		{ xtype: 'tbfill' },{
			text: '前の曲',
			listeners: {
				'click' : function(b) {
					var artisttab = Ext.getCmp('artisttab');
					if (artisttab.current_music > 0) {
						var music = musicstore.getAt(artisttab.current_music - 1);
						update_artistlist(music);
					}
					return true;
				}
			}
		},{ xtype: 'tbseparator' },{
			text: '次の曲',
			listeners: {
				'click' : function(b) {
					var artisttab = Ext.getCmp('artisttab');
					if (artisttab.current_music < (musicstore.getCount() - 1)) {
						music = musicstore.getAt(artisttab.current_music + 1);
						update_artistlist(music);
					}
					return true;
				}
			}
		}
	);
	function getColumnConfig(left, right) {
		return {
			layout:'column',
			border:false,
			items:[{
				columnWidth:.5,
				layout:'form',
				border:false,
				items: [
					left
				]
			},{
				columnWidth:.5,
				layout:'form',
				border:false,
				items: [
					right
				]
			}]
		};
	}

	var form = new Ext.FormPanel({
		labelAlign: 'top',
		title: 'ディスク情報入力',
		bodyStyle: 'padding:5px',
		width: 600,
		hideBorders: true,
		url: 'input.php',
		method: 'POST',
		standardSubmit: true,
		items: [{
			layout:'form',
			border:false,
			items:[
				getColumnConfig(disc_title, disc_subtitle),
				disc_type,
				getColumnConfig(disc_year,product_no),
				getColumnConfig(label1, label1.label_kana), label1.label_id,
				getColumnConfig(label2, label2.label_kana), label2.label_id,
				condition,
				getColumnConfig(stocker, stocker_caption),
				description,
				keyword
			]
		},{
			xtype: 'tabpanel',
			activeTab: 1,
			height: 400,
			defaults:{
				bodyStyle:'padding:1px'
			},
			layoutOnTabChange: true,
			deferredRender: false,
			id:'maintab',
			items:[{
				title:'収録曲',
				layout:'border',
				id:'musictab',
				defaults: {width: '100%'},
				border:false,
				items:[
					music_list
				]
			},{
				title:'デフォルトアーティスト',
				layout:'border',
				id:'defaultartisttab',
				defaults: {width: '100%'},
				border:false,
				items:[
					default_artist_list
				]
			}]
		}],
		buttons: [
			{text: 'save', handler: submit}
		]
	});
	form.render(document.body);

	function submit() {
		var form_values = form.getForm().getValues();
		var disc = new Object;
		disc.data_version = '1.0';
		disc.title = form_values.disc_title;
		disc.subtitle = form_values.disc_subtitle;
		disc.type = disc_type.getValue();
		disc.year = form_values.disc_year;
		disc.product_no = form_values.product_no;
		disc.stocker = form_values.stocker;
		disc.keyword = form_values.keyword;
		disc.label1 = form_values.label1;
		disc.label2 = form_values.label2;
		disc.description = form_values.description;
		disc.condition = condition.getValue();
		disc.music = new Array();
		musicstore.each(function makeOutput(mrec){
			var music = new Object;
			var artists;

			music.side = mrec.get('side');
			music.track = mrec.get('track');
			music.title = mrec.get('title');
			music.id = mrec.get('id');
			music.year = mrec.get('year');
			artists = mrec.get('artist');
			music.artist = new Array();
			artists.each(function (arec){
				var artist = new Object;
				artist.name = arec.get('artist');
				artist.kana = arec.get('kana');
				artist.id   = arec.get('id');
				artist.category = arec.get('category');
				artist.category_id = arec.get('category_id');
				artist.description = arec.get('description');
				music.artist.push(artist);
			});
			disc.music.push(music);
		});
		var output = Ext.encode(disc);
/*
		var tmpform = new Ext.form.FormPanel({
			url:'input.php',
			method:'POST',
			standardSubmit:true,
			items:[
				new Ext.form.Hidden({
					name: 'data',
					value: output
				})
			]
		});
		tmpform.render(document.body);
		tmpform.getForm().getEl().dom.method = 'POST';
		tmpform.getForm().getEl().dom.action = 'input.php';
		tmpform.getForm().submit();
*/
///*
		Ext.Ajax.request({
			url:'input.php',
			method:'POST',
			params: {data:output},
			success:function(response){
				var res = Ext.util.JSON.decode(response.responseText);
				if (!res['success']) {
					alert('register failed\n'+res['error']);
				} else {
					alert('successfully registered');
				}
			},
			failure:function(){alert("register failed");}
		});
//*/
	}
});

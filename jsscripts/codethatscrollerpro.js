// CodeThatScroller PRO
// Version: 1.2.4 (11.14.2004.1)
// IT IS ILLEGAL TO USE UNREGISTERED VERSION OF THE SCRIPT. WE PERFORM
// MONITORING OF THE SITES THAT USE SCRIPT USING GOOGLE AND SPECIAL WORDS
// INCLUDED INTO THE SCRIPT. WE WILL INITIATE LEGAL ACTIONS AGAINST THE
// PARTIES THAT VIOLATE LICENSE AGREEMENT. PLEASE REGISTER THE SCRIPT.
// Copyright (c) 2003-2004 by CodeThat.Com
// http://www.codethat.com/

var CT_IMG_BLANK = 'img/1x1.gif';

function CT_pre ( src ) { return CodeThat.preload(src) }
function pI (s) { return parseInt(s) }

function CT_s_addLr(w,h,bg,im,css,x,y) {
	var l = new CLayer();
	l.setBgColor(bg);
	l.setBgImage(im);
	l.setCSS(css);
	l.setPos(x,y);
	l.resize(w,h);
	return l
}

function CScroller (def, id) {
	this._id = id;
	def = def || {};
//<!--
	this.save = def.savestate;
	if (Undef(this.save)) this.save = true;
//-->
	this.pre = def.preload;
	if (Undef(this.pre)) this.pre = true;
	this.pre_n = pI(def.preload_num);
	this.pre_lim = !isNaN(this.pre_n);
	this.sl = (def.mode || 'scroll') == 'slide'; //default mode is scrolling
	this.effect = def.itemeffect;
	var o = this.style = def.style || {};
	o.width = pI(o.width);
	o.height = pI(o.height);
	o.view = o.view || {};
	o = o.offset = o.offset || {};
	o.x = pI(o.x || 0);
	o.y = pI(o.y || 0);
	o = this.pos = def.position || {};
	o.x = pI(o.x || 0);
	o.y = pI(o.y || 0);
//<!--
	if (Undef(o.absolute))
		o.absolute  = true;
//-->
	this.itemstyle = def.itemstyle || {};
	this.prg = def.progress || {}; //progress layer settings
	o = this.scroll = def.scroll || {};
	o.type = o.type || 'auto';
	this._sc_auto = o.type == 'auto';
	o.step = pI(o.step || 5);
	o.timer = pI(o.timer || 20);
	o.dir = o.dir || 'e';
	o.ctrlpos = o.ctrlpos || {};
	o.ctrlstyle = o.ctrlstyle || {};
	if (Undef(o.control)) o.control = [];
	this.mkCtrl(o);
	this.mkItems(def);
	_CT_scroll[_CT_scroll.length] = this
}

{
var SCp = CScroller.prototype;

SCp.create = function () {
	with (this) {
		var l, w = st('width'), h = st('height');
	//create main container
		this._l = l = CT_s_addLr(w, h, st('bgcolor'), st('bgimg'), st('css'), pos.x, pos.y);
//<!--
		l.setRel(!pos.absolute);
//-->
	//create view area inside
		var v = st('view'), vh = pI(v.height || h), vw = pI(v.width || w);
		this._v = l = CT_s_addLr(vw, vh, '', '', '', v.offset.x, v.offset.y);
		if (!sl) {
			l.addEventHandler("onMouseOver", _id+".mscr_pause()");
			l.addEventHandler("onMouseOut", _id+".mscr_pause()")
		}
	//create "loading..." layer
		this._ld = l = CT_s_addLr('','', prg.bgcolor, prg.bgimg, prg.css);
		l.setHTML(prg.title || 'Loading...');
		l.setZIndex(items.length+10)
/*STD_UNREG
        //'CodeThat.Com' layer for unregistered version
		this.ctc = l = CT_s_addLr(150,20,'','','',pos.x+2,pos.y+h);
		var ln = unescape('%43%6F%64%65%54%68%61%74%2E%43%6F%6D');
		l.setHTML(ln.fontcolor('#AAAAAA').fontsize(-2).link('http://'+ln))
*/
//<!--
	//create comment area if specified
		var c = st('comment');
		if (Def(c)) {
			var s = c.style || {}, cw = pI(c.width || 20), ch = pI(c.height || 5), p = c.offset = c.offset || {};
			p.x = pI(p.x || v.offset.x);
			p.y = pI(p.y || (v.offset.y + vh + 2));
			l = c._l = new CLayer();
			l.setPos(p.x, p.y);
			c._id = CodeThat.newID();
			l.setHTML('<div align=left><form name='+c._id+'><textarea name=txt'+
				(Def(s.css) ? ' class='+ s.css : '')+
				(!ua.nn4 ? 
				' style="'+ (Def(s.bgcolor) ? ';background-color:'+s.bgcolor : '')+
				(Def(s.bgimg) ? ';background-image:url('+s.bgimg+')':'')+
				(Def(s.color) ? ';color:'+s.color : '')+
				(Def(s.align) ? ';text-align:'+s.align : '')+'"':'')+' cols='+cw+' rows='+ch+' wrap=virtual></textarea></form></div>');
			this.com = l.getSource()
		}
//-->
	//make source and create
		mkSrc();
		_l.create();
/*STD_UNREG	ctc.create(); */
		assign()
	}
};

SCp.st = function (key) {
	return this.style[key]
};

SCp.view = function (key) {
	return this.st('view')[key]
};

SCp.scr = function (key) {
	return this.scroll[key]
};

SCp.assign = function () {
	with (this) {
		_v.assignLayer();
		_ld.assignLayer();
/*STD_UNREG
		ctc.assignLayer();
*/
		var i, c;
//<!--
		if (Def(c = st('comment'))){
			c._l.assignLayer();
			c.obj = (ua.nn4 ? c._l.object() : window).document.forms[c._id].txt;
		}
//-->
		for (i=0; i<items.length;)
			items[i++].l.assignLayer();
		for (i=0; i<_ct.length;)
			_ct[i++].assignLayer()
	}
};

SCp.itMove = function () {
	var i=0, x, y, it = this.items;
	var sc_i = this._sc_i || 0;
	for (;i<it.length;i++) {
		x = i < sc_i ? this.sl ? 0 : it[i].mx : it[i].ix;
		y = i < sc_i ? this.sl ? 0 : it[i].my :	it[i].iy;
		it[i].l.setPos(x,y);
		if (this.sl) it[i].l.hide();
	}
};

SCp.itSize = function () {
	with (this) {
		var i, iw, ih, it = items, z = 1;
		var dir = scr('dir'), vw = view('width'), vh = view('height');
		this.rev = !sl && (dir == 'e' || dir == 's');
		for (i=0;i<it.length;i++) {
			iw = Math.max(it[i].l.getContentWidth(), it[i].style.width);
			ih = Math.max(it[i].l.getContentHeight(), it[i].style.height);
			if (sl)
				it[i].l.setZIndex(z++);
			it[i].ix = dir == 'w' ? vw : dir == 'e' ? -iw : 0;
			it[i].iy = dir == 'n' ? vh : dir == 's' ? -ih : 0;
			it[i].mx = dir == 'w' ? -iw : dir == 'e' ? vw : 0;
			it[i].my = dir == 'n' ? -ih : dir == 's' ? vh : 0;
			it[i].jx = hor ? Math.max(iw, vw) : 0;
			it[i].jy = !hor ? Math.max(ih, vh) : 0;
			it[i].l.resize(iw,ih);
			it[i].l.setPos(-iw,-ih)
		}
	}
};

SCp.mkSrc = function () {
	var i, src = '', it = this.items, nn6 = ua.nn6up && !ua.nn7up;
	//make items' source
	for (i=0; i<it.length; i++)
		src += it[i].l.getSource();
	with (this) {
		//make view layer source
		_v.setHTML((nn6 ? '<div style="position:relative">':"")+src+_ld.getSource()+(nn6 ? '</div>':""));
		//make main container source
		_l.setHTML(_v.getSource() + ctrlsrc
//<!--
			+ (this.com || '')
//-->
		)
	}
};

SCp.mkItems = function (def) {
	with (this) {
		var i, im, iw, ih, s, w = view('width'), h = view('height'), it = def.items;
		var set_h, dir = scr('dir'), def_st = this.itemstyle;
		this.hor = dir == 'e' || dir == 'w';
		this.items = [];
		this._pre = 0;
		if (it.length == 1 && !sl)
			it[1] = {src:'',style:{width:0,height:0}};
		for (i=0; i<it.length; i++) {
			if (Def(it[i])) {
				im = it[i];
				s = im.style = im.style || {};
				// y = -1 is a fix for old Operas' clipping.
				// If Opera sees that all layers are visible at the moment of creation, it does not do clipping...:(
				set_h = Def(s.height || def_st.height);
				iw = s.width = pI(s.width || def_st.width ||w); ih = s.height = pI(s.height || def_st.height || h);
				im.l = CT_s_addLr(iw, '', s.bgcolor || def_st.bgcolor, s.bgimg || def_st.bgimg, s.css || def_st.css, 0,-1);
				if (hor || set_h)
					im.l.setHeight(ih);
				im.img = im.type == 'IMG';
				if (!im.img) {
					src = im.src;
					var par = s.color || def_st.color;
					if (Def(par))
						src = '<font color="'+par+'">'+src+'</font>';
					par = s.align || def_st.align;
					if (Def(par))
						src = '<div align="'+par+'">'+src+'</div>';
					im.l.setHTML(src)
				}
				else {
					var a = im.act, anch = Def(a);
					var loadimg = pre && (!pre_lim || _pre < pre_n);
					im.nm = CodeThat.newID();
					im.l.setHTML(
						(anch ? '<a href="'+(a.url || '#')+'"'+(Def(a.target) ? ' target="'+a.target+'"' : '')+
						(Def(a.js) ? ' onclick="'+a.js+'"' : '') + (Def(a.title) ? ' title="'+a.title+'"' : '')+'>':'')+
						'<img '+(ua.nn4 ? 'name=': 'id=')+im.nm+' src="'+(loadimg ? im.src : CT_IMG_BLANK)+'" width="'+iw+'" height="'+ih+'" border=0>'+
						(anch ? '</a>' : '')
					);
					if (loadimg) {
						im._o = CT_pre(im.src);
						_pre++
					}
				}
				items[items.length] = im;
			}
		}
	}
};

SCp.mkCtrl = function (def) {
	var i, p, main_st, st, ctrl_src, ctrl_js, src = '';
	var cx, cy, cpos = def.ctrlpos, it = def.control;

	cx = pI(cpos.x || 0);
	cy = pI(cpos.y || 0);

	main_st = def.ctrlstyle;

	this._ct = [];
	for(i=0; i<it.length; i++) {
		var al, lw, lh;
		//layer we need to take the source code from
		st = it[i].style || {};
		this._ct[this._ct.length] = lr = CT_s_addLr('','', st.bgcolor || main_st.bgcolor, st.bgimg || main_st.bgimg, st.css || main_st.css, cx, cy);
		lw = pI(st.width || main_st.width);
		lh = pI(st.height || main_st.height);
		if (Def(lw))
			lr.setWidth(lw);
		if (Def(lh))
			lr.setHeight(lh);

		//action js
		ctrl_js = this._id + '.mscr_'+it[i].act.toLowerCase()+'()';

		if (it[i].type == 'link')
			ctrl_src = '<a href="#" onclick="'+ ctrl_js+';if(Def(this.blur))this.blur();return false" title="'+it[i].act+'">'+it[i].text+'</a>'
		else
			ctrl_src = '<form><input type=button value="'+(it[i].text || it[i].act)+'" onclick="'+ctrl_js+'"'+(Def(lr.getCSS())? ' class="'+lr.getCSS()+'"':'')+'></form>';
		al = st.align || main_st.align;
		if (Def(al))
			ctrl_src = '<div align='+al+'>'+ctrl_src+'</div>';
		lr.setHTML(ctrl_src);
		if (Def(p = it[i].pos))
			lr.setPos(pI(p.x || 0), pI(p.y || 0));
		else {
			if (def.ctrldir == 'v')
				cy += lh
			else
				cx += lw
		}
		lr.setZIndex(2);
		//mmm...not good, but it's worth it... Opera 5 relative positioning fix.
		lr.cx = lr.getLeft();
		lr.cy = lr.getTop();
		src += lr.getSource();
	}
	this.ctrlsrc = src
};

SCp.moveLr = function () {
	with (this) {
		var c, p = view('offset');
		_ld.setPos(0,0);
		_v.setPos(p.x, p.y);
//<!--
		if (Def(c = st('comment')) && (p = c.offset))
			c._l.setPos(p.x, p.y);
//-->
		for (var i=0; i<_ct.length;i++) {
			_ct[i].setLeft(_ct[i].cx)
			_ct[i].setTop(_ct[i].cy)
		}
	}
};

SCp.run = function (b) {
	if (!ua.oldOpera || b) {
		with (this) {
			var i, c;
			itSize();
			moveLr(); //opera5 relative positioning fix... :(
			_l.show();
			_v.show();
/*STD_UNREG
			ctc.show();
*/
//<!--
			if (Def(c = st('comment')))
				c._l.show();
//-->
			for (i=0; i<items.length;)
				items[i++].l.show();
			for (i=0; i<_ct.length;)
				_ct[i++].show();
			sl ? sl_init(1) : scr_init(1);
		}
	}
};

SCp.scr_act = function (o, o2, js, b) {
	with (this) {
		var a = [o.l], c = [ b ? rev ? [o.ix,o.iy] : [o2.jx,o2.jy] : [0,0]], An = CodeThat.Ani;
		if (Def(o2)) {
			a[a.length] = o2.l;
			c[c.length] = b ? [0,0] : rev ? [o.jx, o.jy] : [o2.mx,o2.my]
		}
		this._sc_o = An.obj(An.add(An.SLIDE, true, a, c, scr('step'), scr('timer'), null, null, js));
		_sc_o.run()
	}
};

SCp.scr_step = function () {
	with (this) {
		var oj, oi = items[_sc_i];
		oi.l.moveTo(oi.ix, oi.iy);
		if (Def(_sc_j)) {
			oj = items[_sc_j];
			if (!rev)
				oi.l.moveTo(oj.jx, oj.jy)
		}
		act(oi, (oj ? oj : null), _id+'.scr_done()');
	}
};

SCp.sl_done = SCp.scr_done = function (b) { //b is a 'back' flag
	this._sc_o = null;
	this._flt_o = null;
	with (this) {
//<!--
		wr_comm(items[_sc_i].comment);
		if (save) wr_cook();
//-->

		if (sl && !b && Def(items[_sc_j]))
			items[_sc_j].l.hide();

		if (_sc_auto && !_paused)
			mscr_next()
	}
};

SCp.sl_pause = SCp.scr_pause = function (js) {
	this._sc_to = setTimeout(this._id+'._sc_to=null;'+js, this.scr('pause'))
};

SCp.sl_init = SCp.scr_init = function (c) {
	this._lto = null; //clear check-image-loaded timeout
	this._paused = false;
	with (this) {
//<!--
		if (!c || !save || !rd_cook()) {
//-->
			this._sc_i = 0;
			this._sc_j = null
//<!--
		}
//-->
		itMove();
		sl ? sl_step() : scr_step()
	}
};

//<!--
SCp.wr_comm = function (txt) {
	with (this) {
		var c, o;
		if (Undef(c = st('comment'))) return;
		var o = c.obj;
		txt = txt || '';
		if (Def(o))
			o.value = txt;
	}
};

SCp.wr_cook = function () { CodeThat.writeCookie('Sld'+this._id, this._sc_i, 86400000) };

SCp.rd_cook = function () {
	with (this) {
		var s = CodeThat.readCookie('Sld'+_id);
		this._sc_i = pI(s);
		if (isNaN(_sc_i) || _sc_i >= items.length)
			return 0;
		this._sc_j = _sc_i > 0 ? _sc_i-1 : null;
		return 1
	}
};
//-->

SCp.loaded = function (o) {
	if (!o.img) return 1;
	if (Undef(o._o))
		o._o = CT_pre(o.src);
	var compl = o._o.complete;
	if (compl) {
		var image = CodeThat.findElement(o.nm);
		if (Def(image))
			image.src = o._o.src
	}
	return compl
};

SCp.act = function (o, o2, js, b) {
	with (this) {
		//wait until required image is loaded
		if (!loaded(b ? o2 : o)) {
			var oref = this;
			_ld.show();
			_lto = setTimeout(function(){oref.act(o,o2,js,b)},50);
			return
		} else {
			_ld.hide();
			_lto = null;
		}
		sl ? sl_act(o, o2, js, b) : scr_act(o, o2, js, b)
	}
};

SCp.sl_act = function (o, o2, js, b) {
	with (this) {
//<!--
		var ef = o.effect || effect;
		if (ef && ua.ie55up && !b) {
			var l = o.l.object();
			l.style.filter = ef;
			o.l.hide();
			o.l.moveTo(0,0);
			l.filters[0].apply();
			o.l.show();
			l.onfilterchange = new Function(js);
			l.filters[0].play();
			this._flt_o = l //object with filter being applied
		} else  {
//-->
			o.l.show();
			if (o2) o2.l.show();
			this._sc_o = CodeThat.Ani.obj(o.l.slide(b ? o.ix : 0, b ? o.iy : 0, scr('step'), scr('timer'), null, null, js))
//<!--
		}
//-->
	}
};

SCp.sl_step = function () {
	with (this) {
		var oj, oi = items[_sc_i];
		if (Def(_sc_j))
			oj = items[_sc_j];
		oi.l.moveTo(oi.ix, oi.iy);
		act(oi, oj, _id+'.sl_done()')
	}
};

SCp.mscr_start = function () {
	with (this) {
//		if (!_sc_auto) return;
		if (Def(_lto)) return;
		_paused = false;
		if(Def(this._sc_o)) {
			if (_sc_o.paused())
				_sc_o.on()
		} else if (Undef(this._sc_to)) {
			/*if (!sl)*/ _sc_i = _sc_j--;//_sc_i--;
			mscr_next()
		}
	}
};

SCp.mscr_stop = function () {
	with (this) {
//		if (!_sc_auto) return;
		_paused = true;
		if(Def(this._sc_o)) {
			if (!_sc_o.paused())
				_sc_o.pause()
		} else if (Def(this._sc_to)) {
			clearTimeout(_sc_to);
			_sc_to = null;
//<!--
		} else if (Def(this._flt_o)) {
			_flt_o.filters[0].stop();
			if (_flt_o)
				_flt_o.fireEvent('onfilterchange');
			_sc_j = _sc_i;
//-->
		} else if (Def(this._lto)) {
			clearTimeout(_lto);
			_lto = null
		}
	}
};

SCp.jsrun = function (js) {
	if (this._sc_auto)
		this.scr_pause(js)
	else
		eval(js)
}

SCp.mscr_next = function () {
	if (Def(this._sc_o) || Def(this._sc_to) || Def(this._lto)) return;
	with (this) {
		_sc_j = _sc_i++;
		if (_sc_i == items.length) {
			if (scr('cycle')) {
				if (sl) {
					jsrun(_id+'.sl_init()');
					return
				}
				else
					_sc_i = 0;
			} else {
				_sc_i = _sc_j--;
				return
			}
		}
		jsrun(_id + (sl ? '.sl' : '.scr') + '_step()')
	}
};

SCp.mscr_prev = function () {
	with (this) {
		if (_sc_auto || Def(this._sc_o) || Def(this._sc_to) || Def(_lto)) return;
		var o, o2, it=items, ln = it.length, lr = _id + '.items[0].l.set';
		if (sl) {
			if (_sc_i > 0) {
				o = it[_sc_i--];
				act(o, it[_sc_i], _id + '.sl_done(1)', true)
			} else if (scr('cycle') && ln > 1) {
				it[0].l.setZIndex(ln+1);
				_sc_i = ln;
				itMove();
				_sc_i--;
				act(it[0], it[_sc_i], lr+'ZIndex(1);'+lr+'Pos(0,0);'+_id+'.sl_done(1)', true);
			}
		} else {
			if (Undef(_sc_j)) {
				if (!scr('cycle')) return;
				_sc_j = ln-1;
			}
			o = it[_sc_i]; o2 = it[_sc_j];
			o2.l.moveTo(rev ? -o.ix : o2.mx, rev ? -o.iy : o2.my);
			act(o, o2, _id+'.scr_done(1)', 1);
			_sc_i = _sc_j--;
			if (_sc_j < 0) {
				if (scr('cycle'))
					_sc_j = it.ln-1
				else {
					_sc_j = _sc_i++;
					return
				}
			}
		}
	}
};

SCp.mscr_pause = function () {
	with (this) {
		if (!_sc_auto && !Def(this._sc_o) || Def(_lto)) return;
		_paused = !_paused;
		if (Def(this._sc_o))
		{
			if (_sc_o.paused())
				_sc_o.on()
			else
				_sc_o.pause()
		} else if (Def(this._sc_to)) {
			clearTimeout(_sc_to);
			_sc_to = null
//<!--
		} else if (Def(this._flt_o)) {
			_flt_o.filters[0].stop();
			if (_flt_o) //in case the onfilterchange handler was not called
				_flt_o.fireEvent('onfilterchange');
			_sc_j = _sc_i;
//-->
		} else {
			/*if (!sl)*/ _sc_i = _sc_j--;
			mscr_next()
		}
	}
};

SCp.mscr_rew = function () {
	with (this) {
		mscr_stop();
		if (sl)
			sl_init()
		else
			scr_init()
	}
}

}

var _CT_scroll = [];

function CT_s_load () {
	for (var i=0;i<_CT_scroll.length;)
		_CT_scroll[i++].run(1);
	CodeThat.setOnResize(CT_s_res, true)
}

function CT_s_res () {
	if (Undef(window._CT_reloading)) {
		window._CT_reloading = true;
		location.reload(true)
	}
}

if (ua.oldOpera)
	CodeThat.setOnLoad(CT_s_load)
else if (ua.nn4)
	CodeThat.setOnResize(CT_s_res);
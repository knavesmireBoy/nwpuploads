/*jslint nomen: true */
/*global window: false */
/*global poloAF: false */
/*global document: false */
/*global _: false */
if (!window.poloAF) {
	window.poloAF = {};
}
(function () {
	"use strict";
	var utils = poloAF.Util,
		$ = function (str) {
			return document.getElementById(str);
		},
		ptL = _.partial,
		anCr = utils.append(),
		anCrIn = utils.insert(),
		setAttrs = utils.setAttributes,
		clicker = ptL(utils.addHandler, 'click'),
		isEqual = function (char) {
			return function (arg) {
				return arg === char;
			};
		},
        mylist = [[/\n+1\.\s+/g, '\n- '], [/\n+\-\s+/g, '\n1. ']],
        doAlt = utils.doAlternate(),
        toggleToolbar = doAlt([ptL(utils.show, ptL($, 'guide')), ptL(utils.hide, ptL($, 'guide'))]),
		Maker = function (tx, inp) {
			var endlinkref = /\[(\d)+\]:.+/g,
                emphasis = /\**([^\*]+)\**/g,
				i = 0,
				getReg = function (n) {
					return new RegExp('\\[' + n + '\\]:');
				},
				getCurrent = function () {
					var ret = tx.value.match(endlinkref);
					return ret ? Number(ret[ret.length - 1].slice(1, 2)) + 1 : 1;
				},
				prepareId = function (str) {
					if (str === inp.value) {
						var ret = '{id=' + str.replace(/\s/g, '').toLowerCase() + '}';
						return ret.replace('the', '');
					}
					return '';
				},
				trimFrom = function (str, from) {
					if (/^\s+.+/.test(str)) {
						return from + 1;
					}
					return from;
				},
				trimTo = function (str, to) {
					if (/\s+$/.test(str)) {
						return to - 1;
					}
					return to;
				},
				fixFrom = function (tx, from, func) {
					var i = 0;
					while (!func(tx.value.slice(from - 1, from))) {
						i+=1;
						from -= 1;
					}
					return i;
				},
				fixTo = function (tx, to, func) {
					var i = 0;
					while (!func(tx.value.slice(to, to + 1))) {
						i+=1;
						to += 1;
					}
					return i;
				},
				isSelected = function (a, b) {
					return a !== b;
				},
				charCount = function (str, char) {
					var i = 0;
					if (!char) {
						return;
					}
					while (str.charAt(i) === char) {
						i += 1;
					}
					return i;
				},
				fixSelection = function (doFrom, doTo) {
					doTo = doTo || doFrom;
					var from = tx.selectionStart,
						to = tx.selectionEnd,
						cur = tx.value.slice(from, to),
						selected = isSelected(from, to);
					if (selected) {
						from = trimFrom(cur, from);
						to = trimTo(cur, to);
						cur = tx.value.slice(from, to);
					}
					//expand selection
					from -= fixFrom(tx, from, doFrom);
					to += fixTo(tx, to, doTo);
					return {
						from: from,
						to: to
					};
				},
				setTextArea = function (from, to, cur) {
					tx.value = tx.value.slice(0, from) + cur + tx.value.slice(to);
				},
                hasEmphasis = isEqual('*'),
				isSpace = isEqual(' '),
				isLine = isEqual('\n'),
				isStop = isEqual('.'),
				header = 0,
                listFromLine = function () {
					var o = fixSelection(isLine, isLine),
						from = o.from,
						to = o.to;
					if (!isSelected(from, to)) {
						return;
					}
                    /*THIS toggles:
                    a
                    b
                    c
                    - a
                    - b
                    - c
                    a
                    b
                    c
                    1. a
                    1. b
                    1. c
                    IF
                    */
                    if (tx.value.slice(from, to).charAt(0) === '-' || tx.value.slice(from, to).match(/^1\./)) {
						setTextArea(from - 1, to, tx.value.slice(from - 1, to).replace(mylist[0][0], '\n'));
					}
                    else {
						setTextArea(from - 1, to, tx.value.slice(from - 1, to).replace(/(\n|$)/g, mylist[0][1]));
                        tx.value = tx.value.replace(/(\-|\W1\.)\s+(\n+)/, '$2');
                        mylist = mylist.reverse();
					}
				},
                cache = tx.value;
			return {
                list: function () {
					var from = tx.selectionStart,
						to = tx.selectionEnd;
                    // tx.setSelectionRange(from, to);
					if (!isSelected(from, to)) {
						return;
					}
                    if(tx.value.substring(from, to).match(/\n/)){
                        return listFromLine();
                    }
                    else {
						setTextArea(from, to, tx.value.slice(from - 1, to).replace(/(\w+(\s|$))/g, '- $1\n'));
                        mylist = mylist.reverse();
					}
				},
				link: function () {
					var o,
                        fixed = false,
                        t,
                        title = '',
                        res = window.prompt('Enter hyperlink'),
						mybreak = '\n[',
						from = tx.selectionStart,
						to = tx.selectionEnd,
						cur = tx.value.slice(from, to);
					if (!isSelected(from, to)) {
                        o = fixSelection(isSpace, isSpace);
						from = o.from;
						to = o.to;
						cur = tx.value.slice(from, to);
                        fixed = true;
                    }
                    if(!fixed){
                        from = trimFrom(cur, from);
                        to = trimTo(cur, to); 
                    }
					
					if (res) {
                        t = res.indexOf(' ');
                        if(t >= 0){
                            title = '"'+ res.substring(t+1)+'"';
                            res =  res.substring(0, t+1);
                        }
						i = getCurrent(cur);
						mybreak = (i === 1) ? '\n\n[' : mybreak;
						tx.value = tx.value.slice(0, from) + '[' + tx.value.slice(from, to) + '][' + i + ']' + tx.value.slice(to) + mybreak + i + ']: ' + res + title + '{target=blank}' + prepareId(tx.value.slice(from, to)) ;
					}
				},
				unlink: function () {
					tx.focus();
					var n,
						end,
						from = tx.selectionStart,
						to = tx.selectionEnd,
						cur = tx.value.slice(from, to);
					if (!isSelected(from, to)) {
						return;
					}
					from = trimFrom(cur, from);
					to = trimTo(cur, to);
					cur = tx.value.slice(from, to);
					n = cur.slice(-2, -1);
					end = tx.value.search(getReg(n));
					n = n === 1 ? 7 : 6;
					tx.value = tx.value.slice(0, from) + cur.slice(1, -4) + tx.value.slice(to);
					//setTextArea(from, to, cur.slice(1, -4))
					tx.value = tx.value.slice(0, end - n);
				},
				img: function () {
					var t,
                        title,
                        o = fixSelection(isLine),
						from = o.from,
						to = o.to,
						cur = tx.value.slice(from, to),
						res = window.prompt('Enter path to image');                       
                    
					if (res) {
                        t = res.lastIndexOf(' ');
                        if(t >= 0){
                            title = '"'+ res.substring(t+1)+'"';
                            res =  res.substring(0, t+1);
                            res = res + ' ' + title;
                        }
						setTextArea(from, to, '![' + cur + '](' + res + ')');
					}
				},

				bold: function () {
                    //cursor may be in first or last word in a para
                    //for first word we need to find the GREATER 'from' value
                    //for last word the LESSER 'to' value 
					var o = fixSelection(isSpace, isSpace),
                        o2 = fixSelection(isLine, isLine),
						from = Math.max(o.from, o2.from),
                        to = Math.min(o.to, o2.to),
						cur = tx.value.slice(from, to);
                   
					if (hasEmphasis(cur.charAt(0))) { //bold, italics, both
						if (!hasEmphasis(cur.charAt(1))) { //italics
							setTextArea(from, to, cur.replace(emphasis, '***$1***'));
						} else if (hasEmphasis(cur.charAt(2))) { //bold italics
							setTextArea(from, to, cur.replace(emphasis, '*$1*'));
						} else { //bold
							setTextArea(from, to, cur.replace(emphasis, '$1'));
						}
					} else { //normal
						setTextArea(from, to, '**' + cur + '**');
					}
					tx.focus();
				},
				ital: function () {
					var o = fixSelection(isSpace, isSpace),
                        o2 = fixSelection(isLine, isLine),
						from = Math.max(o.from, o2.from),
                        to = Math.min(o.to, o2.to),
						cur = tx.value.slice(from, to);
					if (hasEmphasis(cur.charAt(0))) { //bold, italics, both
						if (!hasEmphasis(cur.charAt(1))) { //italics
							setTextArea(from, to, cur.replace(emphasis, '$1'));
						} else if (hasEmphasis(cur.charAt(2))) { //bold italics
							setTextArea(from, to, cur.replace(emphasis, '*$1*'));
						} else { //bold
							setTextArea(from, to, cur.replace(emphasis, '***$1***'));
						}
					} else { //normal
						setTextArea(from, to, '*' + cur + '*');
					}
					tx.focus();
				},
				para: function () {
					var o,
						res = window.confirm('Please check that the selected sentence ends with a FULL STOP. PERIOD...');
					if (res) {
						o = fixSelection(isSpace, isStop);
						//advance cursor to keep period and space with pre-selected text
						setTextArea(o.from, o.to + 2, tx.value.slice(o.from, o.to + 2) + '\n\n');
					}
				},
				line: function () {
					var o = fixSelection(isSpace, isStop);
					setTextArea(o.from, o.to + 2, tx.value.slice(o.from, o.to + 2) + '<br> ');
				},
				heading: function () {
					var o = fixSelection(isLine);
					header = charCount(tx.value.slice(o.from, o.to), '#');
					header += 1;
					if (header === 7) {
						setTextArea(o.from, o.to, '#' + tx.value.slice(o.from, o.to).replace(/#/g, ''));
						header = 1;
					} else {
						setTextArea(o.from, o.to, '#' + tx.value.slice(o.from, o.to));
					}
					tx.focus();
				},
                back: function(){
                    tx.value = cache;
                },
				setCount: function (count) {
					this.count = count;
				},
                help: function(){
                    toggleToolbar();
                }
			}; //ret
		},
		linkeroo = function (maker) {
			return function (e) {
				if (e.target.alt) {
					var txt = e.target.alt.toLowerCase(),
						func = maker[txt];
					if (func) {
						func();
					}
				}
			};
		};
    
    window.addEventListener('load', function () {
        if(!$('content')){
            return;
        }
        var controlsconf = {
				id: 'controls'
			},
            check = function(li){
                var sib = utils.getNextElement(li.nextSibling);
                if(!sib){
                    toggleToolbar();
                }
            },
			tags = ['HEADING', 'BOLD', 'ITAL', 'PARA', 'LINE', 'LINK', 'UNLINK', 'LIST', 'IMG', 'BACK', 'HELP'],
            tag_titles = ['Create headings from h1 thru h6', 'toggle bold text', 'toggle italic text', 'paragraph shortcut', 'line break shortcut', 'create a link from selected text', 'unlink selected text', 'toggle from paragraph to list', 'insert an image', 'clear all edits', 'toggle a handy guide'],
			$el = utils.machElement(utils.addEvent(clicker, linkeroo(Maker($('content'), $('title')))), ptL(setAttrs, controlsconf), anCrIn($('guide'), $('content').parentNode), utils.always('ul')),
			prepIcons = function (str, i) {
				var mystr = str.toLowerCase(),
					path = '../images/resource/edit_' + mystr + '.png',
					conf = {
						src: path,
						alt: mystr,
                        title: tag_titles[i]
					},
					makeLI = _.compose(anCr($('controls')), utils.always('li'));
				_.compose(ptL(setAttrs, conf), anCr(makeLI), utils.always('img'))();
			};
		$el.render();
		_.each(tags, prepIcons);
         utils.safeAddEvent('pass', clicker, _.compose(check, utils.drillDown(['target'])))(ptL($, 'guide'));
	});
}());
var arkItUpPreviewId,htmlValue ='';
function Markdown(text){
	if(text == undefined || text == '')
	{
		return null;
	}
	function String_r(target, num){
		var buf = "";
		for ( var i = 0; i < num; i++) {
			buf += target;
		}
		return buf;
	}
	function String_trim(target, charlist){
		var chars = charlist || " \t\n\r";
		return target.replace(new RegExp("^[" + chars + "]*|[" + chars + "]*$", "g"), "");
	}

	var md_urls = new Object;
	var md_titles = new Object;
	var md_html_blocks = new Object;
	var md_html_hashes = new Object;
	var md_list_level = 0;

	var md_empty_element_suffix = " />";
	var md_tab_width = 4;
	var md_less_than_tab = md_tab_width - 1;

	var md_block_tags = 'h[2-3]|blockquote|pre|ol|ul|hr';
	var md_contain_span_tags = "h[2-3]|li";
	var md_auto_close_tags = 'hr|img';

	var md_nested_brackets = '.*?(?:\\[.*?\\])?.*?';

	var md_flag_StripLinkDefinitions_Z = "9082c5d1b2ef05415b0a1d3e43c2d7a6";
	var md_reg_StripLinkDefinitions = new RegExp('^[ ]{0,' + md_less_than_tab
			+ '}\\[(.+)\\]:' + '[ \\t]*' + '\\n?' + '[ \\t]*' + '<?(\\S+?)>?'
			+ '[ \\t]*' + '\\n?' + '[ \\t]*' + '(?:' + '(?=\\s)[\\s\\S]'
			+ '["(]' + '(.*?)' + '[")]' + '[ \\t]*' + ')?' + '(?:\\n+|'
			+ md_flag_StripLinkDefinitions_Z + ')', "gm");
	function _StripLinkDefinitions(text){
		text += md_flag_StripLinkDefinitions_Z;
		var reg = md_reg_StripLinkDefinitions;

		text = text.replace(reg, function($0, $1, $2, $3){
			var link_id = $1.toLowerCase();
			md_urls[link_id] = _EncodeAmpsAndAngles($2);
			if ($3 != "" && $3 != undefined)
				md_titles[link_id] = $3.replace(/\"/, "&quot;");
			return "";
		});

		return text.replace(md_flag_StripLinkDefinitions_Z, "");
	}

	function _HashHTMLBlocks(text){
		text = _HashHTMLBlocks_InMarkdown(text)[0];
		return text;
	}

	function _HashHTMLBlocks_InMarkdown(text, indent, enclosing_tag, md_span){
		indent = indent || 0;
		enclosing_tag = enclosing_tag || "";
		md_span = md_span || false;

		if (text === "")
			return new Array("", "");

		var newline_match_before = /(?:^\n?|\n)*$/g;
		var newline_match_after = /^(?:[ ]*<!--.*?-->)?[ ]*\n/g;

		var block_tag_match = new RegExp('(' + '</?' + '(?:' + md_block_tags
				+ '|' + '(?!\\s)' + enclosing_tag + ')' + '\\s*' + '(?:'
				+ '".*?"|' + '\'.*?\'|' + '.+?' + ')*?' + '>' + '|'
				+ '<!--.*?-->' + '|' + '<\\?.*?\\?>' + '|'
				+ '<!\\[CDATA\\[.*?\\]\\]>' + ')');

		var depth = 0;
		var parsed = "";
		var block_text = "";

		do {
			var r = text.match(block_tag_match);

			if (r == null) {
				parsed += text;
				break;
			}

			var parts = new Array(RegExp.leftContext, RegExp.lastMatch
					|| RegExp.$1, RegExp.rightContext);

			parsed += parts[0];

			var tag = parts[1];
			text = parts[2];

			var matches = parsed.match(/(^\n|\n)((.\n?)+?)$/);
			if (matches != null && (matches[1].match(new RegExp('^[ ]{' + (indent + 4)
							+ '}.*(\\n[ ]{' + (indent + 4) + '}.*)*'
							+ '(?!\\n)$/'), "gm") || matches[1].match(/^(?:[^`]+|(`+)(?:[^`]+|(?!\1[^`])`)*?\1(?!`))*$/) == null)) {
				parsed += tag.charAt(0);
				text = tag.substr(1) + text;
			}
			else if (tag.match(new RegExp('^<(?:' + md_block_tags + ')\\b') || (parsed.match(newline_match_before) && text.match(newline_match_after)))) {
				var parsed_array = _HashHTMLBlocks_InHTML(tag + text, _HashHTMLBlocks_HashBlock, true);
				block_text = parsed_array[0];
				text = parsed_array[1];

				parsed += block_text;
			}
			else if (enclosing_tag !== ''
					&& tag.match(new RegExp('^</?(?:' + enclosing_tag + ')\\b'))) {
				if (tag.charAt(1) == '/')
					depth--;
				else if (tag.charAt(tag.length - 2) != '/')
					depth++;

				if (depth < 0) {
					text = tag + text;
					break;
				}

				parsed += tag;
			}
			else {
				parsed += tag;
			}
		} while (depth >= 0);

		return new Array(parsed, text);
	}

	var md_reg_HashHTMLBlocks = new RegExp('(' + '</?' + '[\\w:$]+' + '\\s*'
			+ '(?:' + '"[\\s\\S]*?"|' + '\'[\\s\\S]*?\'|' + '[\\s\\S]+?'
			+ ')*?' + '>' + '|' + '<!--[\\s\\S]*?-->' + '|'
			+ '<\\?[\\s\\S]*?\\?>' + '|' + '<!\\[CDATA\\[[\\s\\S]*?\\]\\]>'
			+ ')');
	function _HashHTMLBlocks_InHTML(text, hash_function, md_attr){
		if (text === '')
			return new Array('', '');

		var markdown_attr_match = new RegExp('\\s*' + 'markdown' + '\\s*=\\s*'
				+ '(["\'])' + '(.*?)' + '\\1');

		var original_text = text;

		var depth = 0;
		var block_text = "";
		var parsed = "";

		var base_tag_name = "";
		var matches = text.match(/^<([\w:$]*)\b/);
		if (matches != null)
			base_tag_name = matches[1];

		do {
			var r = text.match(md_reg_HashHTMLBlocks);

			if (r == null) {
				return new Array(original_text.substr(0, 1), original_text
						.substr(1));
			}

			var parts = new Array(RegExp.leftContext, RegExp.lastMatch
					|| RegExp.$1, RegExp.rightContext);

			block_text += parts[0];
			var tag = parts[1];
			text = parts[2];

			if (tag.match(new RegExp('^</?(?:' + md_auto_close_tags + ')\\b'))
					|| tag.charAt(1) == '!' || tag.charAt(1) == '?') {
				block_text += tag;
			}
			else {
				if (tag.match(new RegExp('^</?' + base_tag_name + '\\b'))) {
					if (tag.charAt(1) == '/')
						depth--;
					else if (tag.charAt(tag.length - 2) != '/')
						depth++;
				}

				var attr_matches = tag.match(markdown_attr_match);
				if (md_attr && attr_matches != null
						&& attr_matches[2].match(/^(?:1|block|span)$/)) {
					tag = tag.replace(markdown_attr_match, '');

					var md_mode = attr_matches[2];
					var span_mode = (md_mode == 'span' || md_mode != 'block' && tag.match('^<(?:' + md_contain_span_tags + ')\\b') != null);

					var matches = block_text.match(/(?:^|\n)([ ]*?)(?![ ]).*?$/);
					var indent = matches[1].length;

					block_text += tag;
					parsed += hash_function(block_text, span_mode);

					matches = tag.match(/^<([\w:$]*)\b/);
					var tag_name = matches[1];

					var parsed_array = _HashHTMLBlocks_InMarkdown(text, indent,
							tag_name, span_mode);
					block_text = parsed_array[0];
					text = parsed_array[1];

					if (indent > 0) {
						block_text = block_text.replace(new RegExp('^[ ]{1,'
								+ indent + '}', "gm"), "");
					}

					if (!span_mode) {
						parsed += block_text;
					}
					else {
						parsed += block_text;
					}
					block_text = "";
				}
				else {
					block_text += tag;
				}
			}

		} while (depth > 0);

		parsed += hash_function(block_text);

		return new Array(parsed, text);
	}

	function _HashHTMLBlocks_HashBlock(text){
		var key = _md5(text);
		md_html_hashes[key] = text;
		md_html_blocks[key] = text;
		return key;
	}
	function _HashHTMLBlocks_HashClean(text){
		var key = _md5(text);
		md_html_hashes[key] = text;
		return key;
	}

	function _HashBlock(text){
		text = _UnhashTags(text);

		return _HashHTMLBlocks_HashBlock(text);
	}

	function _RunBlockGamut(text, hash_html_blocks){
		hash_html_blocks = (hash_html_blocks == undefined);
		if (hash_html_blocks) {
			text = _HashHTMLBlocks(text);
		}
		text = _DoCodeSpans(text);
		text = _DoBlockQuotes(text);
		text = _DoHeaders(text);
		text = _DoLists(text);
		text = text.replace(/[ ]/g, '&nbsp;').replace(/\t/g, '&nbsp;&nbsp;&nbsp;&nbsp;');
		text = _DoLinks(text);
		text = _FormParagraphs(text);
		
		return text;
	}
	
	var md_reg_DoLinks = new RegExp('((?:[^"\'(=(?&gt;)(?&lt;)])|^|\s*)(http[s]?://[-a-zA-Z0-9@:;%_\+.~#?\\&/=]+)', "gm");
	
	function _DoLinks(text)
	{
		var reg = md_reg_DoLinks;
		text = text.replace(reg, function($0, $1, $2){
			var str = $1 + '<a href="' + $2 + '" class="a" target="_blank">' + $2 + '<\/a>';
			return str;
		});

		return text;
	}
	
	function _RunSpanGamut(text){
		text = _EscapeSpecialChars(text);
		text = _DoImages(text);
		text = _EncodeAmpsAndAngles(text);
		text = _DoItalicsAndBold(text);
		//text = text.replace(/[ ]{2,}\n/g, "<br" + md_empty_element_suffix + "\n");

		return text;
	}

	function _EscapeSpecialChars(text){
		var tokens = _TokenizeHTML(text);

		var text = "";

		for ( var i = 0, len = tokens.length; i < len; i++) {
			var cur_token = tokens[i];
			if (cur_token[0] == 'tag') {
				cur_token[1] = _EscapeItalicsAndBold(cur_token[1]);
				text += cur_token[1];
			}
			else {
				var t = cur_token[1];
				t = _EncodeBackslashEscapes(t);
				text += t;
			}
		}
		return text;
	}

	var md_reg_DoImages1 = new RegExp('(' + '!\\[' + '(' + md_nested_brackets
			+ ')' + '\\]' + '[ ]?' + '(?:\\n[ ]*)?' + '\\[' + '(.*?)' + '\\]'
			+ ')', "g");

	var md_reg_DoImages2 = new RegExp('(' + '!\\[' + '(' + md_nested_brackets
			+ ')' + '\\]' + '\\(' + '[ \\t]*' + '<?(\\S+?)>?' + '[ \\t]*' + '('
			+ '([\'"])' + '(.*?)' + '\\5' + '[ \\t]*' + ')?' + '\\)' + ')', "g");

	function _DoImages(text){
		var reg = md_reg_DoImages1;
		text = text.replace(reg, _DoImages_reference_callback);

		var reg = md_reg_DoImages2;
		text = text.replace(reg, _DoImages_inline_callback);

		return text;
	}

	function _DoImages_reference_callback($0, $1, $2, $3){
		var whole_match = $1;
		var alt_text = $2;
		var link_id = $3.toLowerCase();
		var result = "";

		if (link_id == "") {
			link_id = alt_text.toLowerCase();
		}

		alt_text = alt_text.replace(/\"/, '&quot;');
		if (md_urls[link_id]) {
			var url = md_urls[link_id];
			url = _EscapeItalicsAndBold(url);
			result = '<img src="' + url + '" alt="' + alt_text + '"';
			if (md_titles[link_id]) {
				var title = md_titles[link_id];
				title = _EscapeItalicsAndBold(title);
				result += ' title="' + title + '"';
			}
			result += md_empty_element_suffix;
		}
		else {
			result = whole_match;
		}

		return result;
	}

	function _DoImages_inline_callback($0, $1, $2, $3, $4, $5, $6){
		var whole_match = $1;
		var alt_text = $2;
		var url = $3;
		var title = '';
		if ($6)
			title = $6;

		var alt_text = alt_text.replace('"', '&quot;');
		title = title.replace('"', '&quot;');
		var url = _EscapeItalicsAndBold(url);
		var result = '<img src="' + url + '" alt="' + alt_text + '"';
		if (title) {
			title = _EscapeItalicsAndBold(title);
			result += ' title="' + title + '"';
		}
		result += md_empty_element_suffix;

		return result;
	}

	var md_reg_DoHeaders3 = new RegExp('^[ \\t]*(#{2,3})' + '[ \\t]*' + '([^#]+?)'
			+ '[ \\t]*' + '#*' + '(?:[ ]+\\{#([-_:a-zA-Z0-9]+)\\}[ ]*)?'
			+ '\\n', "gm");
	function _DoHeaders(text){
		var reg = md_reg_DoHeaders3;
		text = text.replace(reg, function($0, $1, $2, $3){
			var str = "<h" + $1.length;
			str += ">" + _RunSpanGamut(_UnslashQuotes($2));
			str += "</h" + $1.length + ">";
			return _HashBlock(str);
		});

		return text;
	}

	var md_flag_DoLists_z = "8ac2ec5b90470262b84a9786e56ff2bf";

	function _DoLists(text){
		var md_marker_ul = '[-]';
		var md_marker_ol = '\\d+\.';
		var md_markers = new Array(md_marker_ul, md_marker_ol);

		for ( var i = 0, len = md_markers.length; i < len; i++) {
			var marker = md_markers[i];

			if (md_list_level)
				var prefix = '(^)';
			else
				var prefix = '(?:(\\n\\n)|^\\n?)';

			text = text + md_flag_DoLists_z;
			var reg = new RegExp(prefix + '(([ ]{0,'
					+ md_less_than_tab + '}(' + marker + ')[ \\t]+'
					+ ')(?:[\\s\\S]+?)(' + md_flag_DoLists_z + '|'
					+ '\\n{2}(?=\\S)))', "gm");

			text = text
					.replace(reg,
							function($0, $1, $2, $3, $4){
								$2 = $2.replace(md_flag_DoLists_z, "");
								var list = $2;
								var list_type = $4.match(new RegExp(md_marker_ul)) != null ? "ul" : "ol";
								var marker = (list_type == "ul" ? md_marker_ul : md_marker_ol);

								list = list.replace(/\n{2,}/g, "");
								var result = _ProcessListItems(list, marker);

								result = "<" + list_type + ">" + result + "</" + list_type + ">\n";
								$1 = ($1) ? $1 : "";
								return $1 + result;
							});

			text = text.replace(md_flag_DoLists_z, "")
		}

		return text;
	}

	var md_flag_ProcessListItems_z = "ae279c3e92b456b96f62b8cf03bbad88";
	function _ProcessListItems(list_str, marker_any){
		md_list_level++;

		list_str = list_str.replace(/\n{2,}$/g, "\n");
		list_str += md_flag_ProcessListItems_z;

		var reg = new RegExp('(\\n)?(^[ \\t\\n]*)(' + marker_any
				+ ')[ \\t]+(([\\s\\S]+?))(?=('
				+ md_flag_ProcessListItems_z + '|\n+\\2(' + marker_any
				+ '[ \\t]+)))', "gm");
		list_str = list_str.replace(reg, function($0, $1, $2, $3, $4){
			var item = $4;

			if ($1 || item.match(/\n{2,}/)) {
				item = _RunBlockGamut(_Outdent(item));
			}
			else {
				item = _DoLists(_Outdent(item));
				item = item.replace(/\n+$/, "");
				item = _RunSpanGamut(item);
			}

			return "<li>" + item + "</li>";
		});

		md_list_level--;
		return list_str.replace(md_flag_ProcessListItems_z, "");
	}

	var md_reg_DoCodeSpans = new RegExp(
			'(?:(^|[\n]*)?)(\{\{\{)[\n]*([\\s\\S]+?[\\s\\S][\{\}]*)(\}\}\})', "g");

	function _DoCodeSpans(text){
		var reg = md_reg_DoCodeSpans;
		text = text.replace(reg, _DoCodeSpans_callback);
		return text;
	}

	function _DoCodeSpans_callback($0, $1, $2, $3){
		var c = $3;
		c = _EncodeBackslashEscapes(c);

		return ($1 ? $1 : '') + _HashBlock("<code>" + c + "</code>");
	}

	function _EncodeCode(str){
		str = _EscapeRegExpChars(str);
		
		str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

		return str;
	}

	var md_reg_DoItalicsAndBold_2 = new RegExp(
			'(((?!\\*\\*)([\\s\\S]))?\\*\\*)' + '(?!\\*\\*)'
					+ '(' + '(' + '[^\\n\\*]+?' + '|'
					+ '\\*(?=[^\\n]\\S)(?!\\*)([^\\n][\\s\\S]+?)(?=[^\\n]\\S)[^\\n][\\s\\S]\\*' + ')+?'
					+ '(?=[^\\n]\\S)\\S)' + '\\*\\*', "g");
	
	var md_reg_DoItalicsAndBold_4 = new RegExp('(((?!\\*)[\\s\\S]|^)\\*)'
			+ '(?=\\S)' + '(?!\\*)' + '(' + '[\\s\\S]+?' + ')' + '\\*', "g");

	function _DoItalicsAndBold(text){

		var reg = md_reg_DoItalicsAndBold_2;
		text = text.replace(reg, "$3<strong>$4</strong>");

		var reg = md_reg_DoItalicsAndBold_4;
		text = text.replace(reg, "$2<em>$3</em>");

		return text;
	}

	var md_flag_DoLxx_z = 'igw84gj82gj82jg8f92h347f823h4f';
	
	function _DoBlockQuotes(text){
		text = text + md_flag_DoLxx_z;
		var md_reg_DoBlockQuotes = new RegExp('((^[ \\t]*>[ \\t]+)(?:[\\s\\S]+?))(' + md_flag_DoLxx_z + '|\\n{2}(?=\\S))', "gm");
		var reg = md_reg_DoBlockQuotes;
		text = text.replace(reg, _DoBlockQuotes_callback).replace(md_flag_DoLxx_z, "");;
		return text;
	}

	var md_reg_DoBlockQuotes_callback_1 = /^[ \t]*>[ \t]?/gm;
	
	function _DoBlockQuotes_callback($0, $1, $2, $3){
		var bq = $1;
		bq = bq.replace(md_flag_DoLxx_z, '');
		bq = bq.replace(md_reg_DoBlockQuotes_callback_1, '');
		bq = _EncodeBackslashEscapes(bq);

		return _HashBlock("<blockquote>" + bq + "</blockquote>");
	}

	function _FormParagraphs(text){
		text = _UnhashTags(text);
		text = String_trim(_RunSpanGamut(text));
		text = text.replace(/\n/g, '<br />');
		return text;
	}

	function _EncodeAmpsAndAngles(text){
		return text.replace(/&(?!#?[xX]?(?:[0-9a-fA-F]+|\w+);)/g, '&amp;').replace(/<(?![a-z\/?\$!])/gi, "&lt;");
	}

	var md_reg_esc_backslash = /\\\\/g;
	var md_reg_esc_backquote = /\\\`/g;
	var md_reg_esc_asterisk = /\\\*/g;
	var md_reg_esc_underscore = /\\\_/g;
	var md_reg_esc_lbrace = /\\\{/g;
	var md_reg_esc_rbrace = /\\\}/g;
	var md_reg_esc_lbracket = /\\\[/g;
	var md_reg_esc_rbracket = /\\\]/g;
	var md_reg_esc_lparen = /\\\(/g;
	var md_reg_esc_rparen = /\\\)/g;
	var md_reg_esc_hash = /\\\#/g;
	var md_reg_esc_period = /\\\./g;
	var md_reg_esc_exclamation = /\\\!/g;
	var md_reg_esc_colon = /\\\:/g;
	function _EncodeBackslashEscapes(text){
		return text.replace(md_reg_esc_backslash,
				"7f8137798425a7fed2b8c5703b70d078").replace(
				md_reg_esc_backquote, "833344d5e1432da82ef02e1301477ce8")
				.replace(md_reg_esc_asterisk,
						"3389dae361af79b04c9c8e7057f60cc6").replace(
						md_reg_esc_underscore,
						"b14a7b8059d9c055954c92674ce60032").replace(
						md_reg_esc_lbrace, "f95b70fdc3088560732a5ac135644506")
				.replace(md_reg_esc_rbrace, "cbb184dd8e05c9709e5dcaedaa0495cf")
				.replace(md_reg_esc_lbracket,
						"815417267f76f6f460a4a61f9db75fdb")
				.replace(md_reg_esc_rbracket,
						"0fbd1776e1ad22c59a7080d35c7fd4db").replace(
						md_reg_esc_lparen, "84c40473414caf2ed4a7b1283e48bbf4")
				.replace(md_reg_esc_rparen, "9371d7a2e3ae86a00aab4771e39d255d")
				.replace(md_reg_esc_hash, "01abfc750a0c942167651c40d088531d")
				.replace(md_reg_esc_period, "5058f1af8388633f609cadb75a75dc9d")
				.replace(md_reg_esc_exclamation,
						"9033e0e305f247c0c3c80d0c7848c8b3").replace(
						md_reg_esc_colon, "853ae90f0351324bd73ea615e6487517");
	}

	var md_reg_md5_backslash = /7f8137798425a7fed2b8c5703b70d078/g;
	var md_reg_md5_backquote = /833344d5e1432da82ef02e1301477ce8/g;
	var md_reg_md5_asterisk = /3389dae361af79b04c9c8e7057f60cc6/g;
	var md_reg_md5_underscore = /b14a7b8059d9c055954c92674ce60032/g;
	var md_reg_md5_lbrace = /f95b70fdc3088560732a5ac135644506/g;
	var md_reg_md5_rbrace = /cbb184dd8e05c9709e5dcaedaa0495cf/g;
	var md_reg_md5_lbracket = /815417267f76f6f460a4a61f9db75fdb/g;
	var md_reg_md5_rbracket = /0fbd1776e1ad22c59a7080d35c7fd4db/g;
	var md_reg_md5_lparen = /84c40473414caf2ed4a7b1283e48bbf4/g;
	var md_reg_md5_rparen = /9371d7a2e3ae86a00aab4771e39d255d/g;
	var md_reg_md5_hash = /01abfc750a0c942167651c40d088531d/g;
	var md_reg_md5_period = /5058f1af8388633f609cadb75a75dc9d/g;
	var md_reg_md5_exclamation = /9033e0e305f247c0c3c80d0c7848c8b3/g;
	var md_reg_md5_colon = /853ae90f0351324bd73ea615e6487517/g;

	function _UnescapeSpecialChars(text){
		return text.replace(md_reg_md5_backslash, "\\").replace(
				md_reg_md5_backquote, "`").replace(md_reg_md5_asterisk, "*")
				.replace(md_reg_md5_underscore, "_").replace(md_reg_md5_lbrace,
						"{").replace(md_reg_md5_rbrace, "}").replace(
						md_reg_md5_lbracket, "[").replace(md_reg_md5_rbracket,
						"]").replace(md_reg_md5_lparen, "(").replace(
						md_reg_md5_rparen, ")").replace(md_reg_md5_hash, "#")
				.replace(md_reg_md5_period, ".").replace(
						md_reg_md5_exclamation, "!").replace(md_reg_md5_colon,
						":");
	}

	function _UnhashTags(text){
		for ( var key in md_html_hashes) {
			text = text.replace(new RegExp(key, "g"), md_html_hashes[key]);
		}
		return text;
	}
	
	function _TokenizeHTML(str){
		var index = 0;
		var tokens = new Array();

		var reg = new RegExp(
				'(?:<!(?:--[\\s\\S]*?--\\s*)+>)|'
						+ '(?:<\\?[\\s\\S]*?\\?>)|'
						+ '(?:<[/!$]?[-a-zA-Z0-9:]+\\b([^"\'>]+|"[^"]*"|\'[^\']*\')*>)', "g");

		while (reg.test(str)) {
			var txt = RegExp.leftContext;
			var tag = RegExp.lastMatch;

			tokens.push([ "text", txt ]);
			tokens.push([ "tag", tag ]);

			str = str.replace(txt, "");
			str = str.replace(tag, "");
		}

		if (str != "") {
			tokens.push([ "text", str ]);
		}

		return tokens;
	}

	var md_reg_Outdent = new RegExp('^(\\t|[ ]{1,' + md_tab_width + '})', "gm");
	
	function _Outdent(text){
		return text;
		//return text.replace(md_reg_Outdent, "");
	}

	function _Detab(text){
		text = text.replace(/(.*?)\t/g, function(match, substr){
			return substr += String_r(" ", (md_tab_width - substr.length % md_tab_width));
		});
		return text;
	}

	function _UnslashQuotes(text){
		return text.replace('\"', '"');
	}

	var md_reg_backslash = /\\/g;
	var md_reg_backquote = /\`/g;
	var md_reg_asterisk = /\*/g;
	var md_reg_underscore = /\_/g;
	var md_reg_lbrace = /\{/g;
	var md_reg_rbrace = /\}/g;
	var md_reg_lbracket = /\[/g;
	var md_reg_rbracket = /\]/g;
	var md_reg_lparen = /\(/g;
	var md_reg_rparen = /\)/g;
	var md_reg_hash = /\#/g;
	var md_reg_period = /\./g;
	var md_reg_exclamation = /\!/g;
	var md_reg_colon = /\:/g;
	function _EscapeRegExpChars(text){
		return text.replace(md_reg_backslash,
				"7f8137798425a7fed2b8c5703b70d078").replace(md_reg_backquote,
				"833344d5e1432da82ef02e1301477ce8").replace(md_reg_asterisk,
				"3389dae361af79b04c9c8e7057f60cc6").replace(md_reg_underscore,
				"b14a7b8059d9c055954c92674ce60032").replace(md_reg_lbrace,
				"f95b70fdc3088560732a5ac135644506").replace(md_reg_rbrace,
				"cbb184dd8e05c9709e5dcaedaa0495cf").replace(md_reg_lbracket,
				"815417267f76f6f460a4a61f9db75fdb").replace(md_reg_rbracket,
				"0fbd1776e1ad22c59a7080d35c7fd4db").replace(md_reg_lparen,
				"84c40473414caf2ed4a7b1283e48bbf4").replace(md_reg_rparen,
				"9371d7a2e3ae86a00aab4771e39d255d").replace(md_reg_hash,
				"01abfc750a0c942167651c40d088531d").replace(md_reg_period,
				"5058f1af8388633f609cadb75a75dc9d").replace(md_reg_exclamation,
				"9033e0e305f247c0c3c80d0c7848c8b3").replace(md_reg_colon,
				"853ae90f0351324bd73ea615e6487517");
	}

	function _EscapeItalicsAndBold(text){
		return text
				.replace(md_reg_asterisk, "3389dae361af79b04c9c8e7057f60cc6")
				.replace(md_reg_underscore, "b14a7b8059d9c055954c92674ce60032");
	}

	var md_md5cnt = 0;
	function _md5(){
		var key = "a3e597688f51d1fc" + (md_md5cnt++) + "ce22217bb70243be";
		return key;
	}

	return (function(text){
		md_urls = new Object;
		md_titles = new Object;
		md_html_blocks = new Object;
		md_html_hashes = new Object;

		text = text.replace(/\r\n|\r/g, "\n");
		text += "\n";
		text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		text = text.replace(/((^|[ \n]+)(?:&gt;)[ ]+)/g, "$2 > ");
		text = _Detab(text);
		text = _HashHTMLBlocks(text);
		text = text.replace(/^[ \t]+$/gm, "");
		text = _StripLinkDefinitions(text);
		text = _RunBlockGamut(text, false);
		text = _UnescapeSpecialChars(text);

		return text;
	}).call(this, text);
}

(function($){
	$.fn.markItUp = function(settings, extraSettings){
		var method, params, options, ctrlKey, shiftKey, altKey;
		ctrlKey = shiftKey = altKey = false;

		if (typeof settings == 'string') {
			method = settings;
			params = extraSettings;
		}

		options = {
			id : '', nameSpace : '', root : '', previewHandler : false,
			previewInWindow : '', // 'width=800, height=600, resizable=yes,
			// scrollbars=yes'
			previewInElement : '', previewAutoRefresh : true,
			previewPosition : 'after', previewTemplatePath : '~/preview.html',
			previewParser : false, previewParserPath : '',
			previewParserVar : 'data', resizeHandle : true, beforeInsert : '',
			afterInsert : '', onEnter : {}, onShiftEnter : {},
			onCtrlEnter : {}, onTab : {}, arkItUpPreviewId : '',
			markupSet : [ { /* set */} ]
		};
		$.extend(options, settings, extraSettings);

		// compute markItUp! path
		if (!options.root) {
			$('script').each(
					function(a, tag){
						miuScript = $(tag).get(0).src
								.match(/(.*)jquery\.markitup(\.pack)?\.js$/);
						if (miuScript !== null) {
							options.root = miuScript[1];
						}
					});
		}

		return this
				.each(function(){
					var $$, textarea, levels, scrollPosition, caretPosition, caretOffset, clicked, hash, header, footer, previewWindow, template, iFrame, abort;
					$$ = $(this);
					textarea = this;
					levels = [];
					abort = false;
					scrollPosition = caretPosition = 0;
					caretOffset = -1;

					options.previewParserPath = localize(options.previewParserPath);
					options.previewTemplatePath = localize(options.previewTemplatePath);

					if (method) {
						switch (method) {
							case 'remove':
								remove();
							break;
							case 'insert':
								markup(params);
							break;
							default:
								$.error('Method ' + method
										+ ' does not exist on jQuery.markItUp');
						}
						return;
					}

					// apply the computed path to ~/
					function localize(data, inText){
						if (inText) {
							return data.replace(/("|')~\//g, "$1"
									+ options.root);
						}
						return data.replace(/^~\//, options.root);
					}

					// init and build editor
					function init(){
						id = '';
						nameSpace = '';
						if (options.id) {
							options.arkItUpPreviewId = '#' + options.id;
							id = 'id="' + options.id + '"';
						}
						else if ($$.attr("id")) {
							id = 'id="markItUp'
									+ ($$.attr("id").substr(0, 1).toUpperCase())
									+ ($$.attr("id").substr(1)) + '"';
							options.arkItUpPreviewId = '#markItUp'
									+ ($$.attr("id").substr(0, 1).toUpperCase())
									+ ($$.attr("id").substr(1));
						}
						if (options.nameSpace) {
							nameSpace = 'class="' + options.nameSpace + '"';
						}
						// $$.wrap('<form></form>');
						$$.wrap('<div ' + id + ' class="markItUp"></div>');
						$$.wrap('<div class="markItUpContainer"></div>');
						$$.css({
							minHeight : options.replyPre ? 110 : 180, height : 'auto',maxHeight:options.replyPre ? 150 : 'auto'
						}).attr('onfocus',
								'$.focus(this,\'\',$.Q.replenish(this))');

						// add the header before the textarea
						header = $('<div class="markItUpHeader"></div>')
								.insertBefore($$);
						$(dropMenus(options.markupSet)).appendTo(header);

						// listen key events
						$$.bind('keydown.markItUp', keyPressed).bind('keyup',
								keyPressed);

						// bind an event to catch external calls
						$$.bind("insertion.markItUp", function(e, settings){
							if (settings.target !== false) {
								get();
							}
							if (textarea === $.markItUp.focused) {
								markup(settings);
							}
						});

						// remember the last focus
						$$.bind('focus.markItUp', function(){
							$.markItUp.focused = this;
						});

					}

					// recursively build header with dropMenus from markupset
					function dropMenus(markupSet){
						var ul = $('<ul></ul>'), i = 0;
						$('li:hover > ul', ul).css('display', 'block');
						$.each(markupSet, function(){
							var button = this, t = '', title, li, j;
							title = (button.key) ? (button.name || '')
									+ ' [Ctrl+' + button.key + ']'
									: (button.name || '');
							key = (button.key) ? 'accesskey="' + button.key
									+ '"' : '';
							if (button.separator) {
								li = $(
										'<li class="markItUpSeparator">'
												+ (button.separator || '')
												+ '</li>').appendTo(ul);
							}
							else {
								i++;
								for (j = levels.length - 1; j >= 0; j--) {
									t += levels[j] + "-";
								}
								li = $(
										(options.replyPre && title =='预览模式' ? '': '<li class="markItUpButton markItUpButton'
												+ t + (i) + ' '
												+ (button.className || '')
												+ '"> <a class="'+(title =='预览模式' ? ($.cookie('previewModel')== 'false' ? '':'cur'):'')+'" onclick="'+(title =='预览模式' ? 'previewModel(this);':'')+'" href="" ' + key
												+ ' title="' + title + '">'
												+ (button.name || '')
												+ '</a></li>')).bind(
										"contextmenu.markItUp", function(){ // prevent
											return false;
										}).bind('click.markItUp', function(){
									return false;
								}).bind("focusin.markItUp", function(){
									$$.focus();
								}).bind('mouseup', function(){
									if (button.call) {
										eval(button.call)();
									}
									setTimeout(function(){
										markup(button)
									}, 1);
									return false;
								}).bind('mouseenter.markItUp', function(){
									$('> ul', this).show();
									$(document).one('click', function(){ // close
										// dropmenu
										// if
										// click
										// outside
										$('ul ul', header).hide();
									});
								}).bind('mouseleave.markItUp', function(){
									$('> ul', this).hide();
								}).appendTo(ul);
								if (button.dropMenu) {
									levels.push(i);
									$(li).addClass('markItUpDropMenu').append(
											dropMenus(button.dropMenu));
								}
							}
						});
						levels.pop();

						return ul;
					}

					// markItUp! markups
					function magicMarkups(string){
						if (string) {
							string = string.toString();
							string = string.replace(/\(\!\(([\s\S]*?)\)\!\)/g,
									function(x, a){
										var b = a.split('|!|');
										if (altKey === true) {
											return (b[1] !== undefined) ? b[1]
													: b[0];
										}
										else {
											return (b[1] === undefined) ? ""
													: b[0];
										}
									});
							// [![prompt]!], [![prompt:!:value]!]
							string = string
									.replace(/\[\!\[([\s\S]*?)\]\!\]/g,
											function(x, a){
												var b = a.split(':!:');
												if (abort === true) {
													return false;
												}
												value = prompt(b[0],
														(b[1]) ? b[1] : '');
												if (value === null) {
													abort = true;
												}
												return value;
											});
							return string;
						}
						return "";
					}

					// prepare action
					function prepare(action){
						if ($.isFunction(action)) {
							action = action(hash);
						}
						return magicMarkups(action);
					}
					// build block to insert
					function build(string){
						var openWith = prepare(clicked.openWith);
						var placeHolder = prepare(clicked.placeHolder);
						var replaceWith = prepare(clicked.replaceWith);
						var closeWith = prepare(clicked.closeWith);
						var openBlockWith = prepare(clicked.openBlockWith);
						var closeBlockWith = prepare(clicked.closeBlockWith);
						var multiline = clicked.multiline;

						if (replaceWith !== "") {
							block = openWith + replaceWith + closeWith;
						}
						else if (selection === '' && placeHolder !== '') {
							block = openWith + placeHolder + closeWith;
						}
						else {
							string = string || selection;

							var lines = [ string ], blocks = [];

							if (multiline === true) {
								lines = string.split(/\r?\n/);
							}
							
							for ( var l = 0; l < lines.length; l++) {
								line = lines[l];
								var trailingSpaces;
								if (trailingSpaces = line.match(/ *$/)) {
									blocks.push(openWith
											+ line.replace(/ *$/g, '')
											+ closeWith + trailingSpaces);
								}
								else {
									blocks.push(openWith + line + closeWith);
								}
							}

							block = blocks.join("\n");
						}

						block = openBlockWith + block + closeBlockWith;
						
						return {
							block : block, openWith : openWith,
							replaceWith : replaceWith,
							placeHolder : placeHolder, closeWith : closeWith
						};
					}

					// define markup to insert
					function markup(button){
						
						var len, j, n, i;
						hash = clicked = button;
						get();
						$.extend(hash, {
							line : "", root : options.root,
							textarea : textarea, selection : (selection || ''),
							caretPosition : caretPosition, ctrlKey : ctrlKey,
							shiftKey : shiftKey, altKey : altKey
						});
						// callbacks before insertion
						prepare(options.beforeInsert);
						prepare(clicked.beforeInsert);
						if ((ctrlKey === true && shiftKey === true)
								|| button.multiline === true) {
							prepare(clicked.beforeMultiInsert);
						}
						$.extend(hash, {
							line : 1
						});

						if ((ctrlKey === true && shiftKey === true)) {
							lines = selection.split(/\r?\n/);
							for (j = 0, n = lines.length, i = 0; i < n; i++) {
								if ($.trim(lines[i]) !== '') {
									$.extend(hash, {
										line : ++j, selection : lines[i]
									});
									lines[i] = build(lines[i]).block;
								}
								else {
									lines[i] = "";
								}
							}

							string = {
								block : lines.join('\n')
							};
							start = caretPosition;
							len = string.block.length
									+ (($.browser.opera) ? n - 1 : 0);
						}
						else if (ctrlKey === true) {
							string = build(selection);
							start = caretPosition + string.openWith.length;
							len = string.block.length - string.openWith.length
									- string.closeWith.length;
							len = len - (string.block.match(/ $/) ? 1 : 0);
							len -= fixIeBug(string.block);
						}
						else if (shiftKey === true) {
							string = build(selection);
							start = caretPosition;
							len = string.block.length;
							len -= fixIeBug(string.block);
						}
						else {
							string = build(selection);
							start = caretPosition + string.block.length;
							len = 0;
							start -= fixIeBug(string.block);
						}
						if ((selection === '' && string.replaceWith === '')) {
							caretOffset += fixOperaBug(string.block);

							start = caretPosition + string.openWith.length;
							len = string.block.length - string.openWith.length
									- string.closeWith.length;

							caretOffset = $$.val().substring(caretPosition,
									$$.val().length).length;
							caretOffset -= fixOperaBug($$.val().substring(0,
									caretPosition));
						}
						$.extend(hash, {
							caretPosition : caretPosition,
							scrollPosition : scrollPosition
						});

						if (string.block !== selection && abort === false) {
							insert(string.block);
							set(start, len);
						}
						else {
							caretOffset = -1;
						}
						get();

						$.extend(hash, {
							line : '', selection : selection
						});

						// callbacks after insertion
						if ((ctrlKey === true && shiftKey === true)
								|| button.multiline === true) {
							prepare(clicked.afterMultiInsert);
						}
						prepare(clicked.afterInsert);
						prepare(options.afterInsert);

						// reinit keyevent
						shiftKey = altKey = ctrlKey = abort = false;
					}

					// Substract linefeed in Opera
					function fixOperaBug(string){
						if ($.browser.opera) {
							return string.length
									- string.replace(/\n*/g, '').length;
						}
						return 0;
					}
					// Substract linefeed in IE
					function fixIeBug(string){
						if ($.browser.msie) {
							return string.length
									- string.replace(/\r*/g, '').length;
						}
						return 0;
					}

					// add markup
					function insert(block){
						if (document.selection) {
							var newSelection = document.selection.createRange();
							newSelection.text = block;
						}
						else {
							textarea.value = textarea.value.substring(0,
									caretPosition)
									+ block
									+ textarea.value.substring(caretPosition
											+ selection.length,
											textarea.value.length);
						}
					}

					// set a selection
					function set(start, len){
						if (textarea.createTextRange) {
							// quick fix to make it work on Opera 9.5
							if ($.browser.opera && $.browser.version >= 9.5
									&& len == 0) {
								return false;
							}
							range = textarea.createTextRange();
							range.collapse(true);
							range.moveStart('character', start);
							range.moveEnd('character', len);
							range.select();
						}
						else if (textarea.setSelectionRange) {
							textarea.setSelectionRange(start, start + len);
						}
						textarea.scrollTop = scrollPosition;
						textarea.focus();
					}

					// get the selection
					function get(){
						textarea.focus();

						scrollPosition = textarea.scrollTop;
						if (document.selection) {
							selection = document.selection.createRange().text;
							if ($.browser.msie) { // ie
								var range = document.selection.createRange(), rangeCopy = range
										.duplicate();
								rangeCopy.moveToElementText(textarea);
								caretPosition = -1;
								while (rangeCopy.inRange(range)) {
									rangeCopy.moveStart('character');
									caretPosition++;
								}
							}
							else { // opera
								caretPosition = textarea.selectionStart;
							}
						}
						else { // gecko & webkit
							caretPosition = textarea.selectionStart;

							selection = textarea.value.substring(caretPosition,
									textarea.selectionEnd);
						}
						return selection;
					}

					// set keys pressed
					function keyPressed(e){
						shiftKey = e.shiftKey;
						altKey = e.altKey;
						ctrlKey = (!(e.altKey && e.ctrlKey)) ? (e.ctrlKey || e.metaKey)
								: false;

						if (e.type == 'keydown' || e.type == 'keyup') {
							if (ctrlKey === true) {
								li = $(
										'a[accesskey="'
												+ ((e.keyCode == 13) ? '\\n'
														: String
																.fromCharCode(e.keyCode))
												+ '"]', header).parent('li');
								if (li.length !== 0) {
									ctrlKey = false;
									setTimeout(function(){
										li.triggerHandler('mouseup');
									}, 1);
									return false;
								}
							}
							if (e.keyCode === 13 || e.keyCode === 10) { // Enter
								// key
								if (ctrlKey === true) { // Enter + Ctrl
									ctrlKey = false;
									markup(options.onCtrlEnter);
									return options.onCtrlEnter.keepDefault;
								}
								else if (shiftKey === true) { // Enter + Shift
									shiftKey = false;
									markup(options.onShiftEnter);
									return options.onShiftEnter.keepDefault;
								}
								else {
									markup(options.onEnter);
									return options.onEnter.keepDefault;
								}
							}
							//markup(options.onEnter);
							// return options.onEnter.keepDefault;

							if (e.keyCode === 9) { // Tab key
								if (shiftKey == true || ctrlKey == true
										|| altKey == true) {
									return false;
								}
								if (caretOffset !== -1) {
									get();
									caretOffset = $$.val().length - caretOffset;
									set(caretOffset, 0);
									caretOffset = -1;
									return false;
								}
								else {
									markup(options.onTab);
									return options.onTab.keepDefault;
								}
							}

							if ($('#markItUpPreviewFrame').attr('id')) {
								$('#markItUpPreviewFrames').html(Markdown($$.val()));
							}
						}
						
						//$$
						
					} //end keyPressed
					
					
					function remove(){
						$$.unbind(".markItUp").removeClass('markItUpEditor');
						$$.parent('div').parent('div.markItUp').parent('div').replaceWith($$);
						$$.data('markItUp', null);
					}

					init();
					htmlValue = $.trim($$.val()).length <= 0 ? '': $$.val();
					arkItUpPreviewId = $$;
					if ($('#markItUpPreviewFrame').length == 0 && $$.attr("id") != 'advanced_editor_reply') {
							addElementDiv();
							
					}
				});
	};

	$.fn.markItUpRemove = function(){
		return this.each(function(){
			$(this).markItUp('remove');
		});
	};

	$.markItUp = function(settings){
		var options = {
			target : false
		};
		$.extend(options, settings);
		if (options.target) {
			return $(options.target).each(function(){
				$(this).focus();
				$(this).trigger('insertion', [ options ]);
			});
		}
		else {
			$('textarea').trigger('insertion', [ options ]);
		}
	};
})(jQuery);

var markdownSettings = {
	nameSpace : 'markdown', // Useful to prevent multi-instances CSS conflict
	//previewParserPath : G_BASE_URL + '/question/ajax/markdown_preview/',
	onShiftEnter : {
		keepDefault : false, openWith : '\n'
	},
	replyPre:false, //编辑回复预览
	markupSet : [
			{
				name : '粗体', key : "B", openWith : '**', closeWith : '**'
			},
			{
				name : '斜体', key : "I", openWith : '*', closeWith : '*'
			},
			{
				separator : '---------------'
			},
			{
				name : '引用', openWith : '\n> '
			},
			{
				name : '代码', openWith : '\n{{{\n', closeWith : '\n}}}'
			},
			{
				separator : '---------------'
			},
			{
				name : '普通列表', openWith : '\n- '
			},
			{
				name : '数字列表', openWith : function(markItUp){
					return '\n' + markItUp.line + '. ';
				}
			},
			{
				separator : '---------------'
			},
			{
				name : '图片', key : "P",openWith:function(){$.uploadPicture()}
				//replaceWith : '![[![图片标题]!]]([![图片链接地址:!:http://]!])'
			}, {
				separator : '---------------'
			}, {
				name : '大标题', key : "1", openWith:'\n## '
			}, {
				name : '小标题', key : "2", openWith : '\n### '
			},{
				separator : '---------------'
			}, {
				name : '预览模式'
			}, {
				name : '清空',openWith:function(){
					$(arkItUpPreviewId).val('');
				}
			} ]
}

// mIu nameSpace to avoid conflict.
miu = {
	markdownTitle : function(markItUp, char){
		heading = '';
		n = $.trim(markItUp.selection || markItUp.placeHolder).length;
		for (i = 0; i < n; i++) {
			heading += char;
		}
		return '\n' + heading + '\n';
	}
}

function previewModel(c){
	var el = $(c);
	el.hasClass('cur') ? el.removeClass('cur') : el.addClass('cur') ;
	$('#markItUpPreviewFrame').length > 0 ? $('#markItUpPreviewFrame').toggle() : addElementDiv();
	$.cookie('previewModel')== 'true' ? $.cookie('previewModel', false, { expires:30}) : $.cookie('previewModel', true, { expires:30}),addElementDiv();
				
}
function addElementDiv(){
	$.cookie('previewModel') == 'false'  ? '' : $('#markItUpPreviewFrame').length > 0 ? $('#markItUpPreviewFrame').show() :
	$('<div class="markItUpPreviewFrame" id="markItUpPreviewFrame" style="border-radius:5px;margin-bottom:10px;"></div>')
		.html('<h2 style="font-size:14px;font-weight:bold;color:#333;padding-bottom:5px;border-bottom:1px solid #ccc;margin-bottom:10px;">预览模式：</h2>'+
		'<div id="markItUpPreviewFrames" class="preview_div tags_Mark" >'+(htmlValue =='' ? '': Markdown(htmlValue))+'</div>')
		.insertAfter($('#markItUpBtn').length > 0 ? $('#markItUpBtn') : $('#file_uploader_question'))
}

$('textarea#answer_content').bind('keypress',function(e){
	var s = $(this);
	  var e = e ? e : window.event;
	  if(e.ctrlKey && e.keyCode == 13 || e.ctrlKey && e.keyCode == 10){
		  
		  if($.trim(s.val()).length <= 0){
			  $.alert('回复不能为空...');
			  return false;
		  }else if($.trim(s.val()).length > 0 && $.trim(s.val()).length <= 10){
			  $.alert('回复内容字数不得少于 10 个字节...');
			  return false;
		  }else{
		  	 $('#question_replay_submit').click();
		  }
	  }
})
/**
 * الرئيسية — assets/home/script.js
 *
 * كارت الكود في الهيرو: «درس مصغّر». الكود بيتكتب ببطء وبوقفات طبيعية عند آخر كل سطر،
 * وكل سطر مهم بيظهر تحته شرح مصري قصير كأن أحمد بيشرحه، وفي الآخر النتيجة بتطلع في
 * التيرمينال. فوق الكارت تابات لغات (Java · OOP · C++ · Dart · Python) ممكن الزائر يضغط عليها،
 * والحركة بتقف لما الماوس يقف على الكارت أو التاب يتخفى، وبتكمّل من نفس المكان.
 * لو المتصفح طالب حركة أقل: الكود الكامل اللي في الـHTML بيفضل زي ما هو، والتابات بتشتغل عادي.
 *
 * @package EduBlink_Child
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-lsh-typewriter]');
	if (!root) {
		return;
	}
	var pre = root.querySelector('[data-lsh-pre]');
	var out = root.querySelector('[data-lsh-out]');
	var notes = root.querySelector('[data-lsh-notes]');
	var tabs = root.querySelectorAll('[data-lsh-tab]');
	if (!pre || !out || !notes) {
		return;
	}

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// السرعة بالمللي ثانية لكل حرف — بطيئة عن قصد عشان تتقرا مش تتفرج عليها بس
	var CHAR_MS = 55;
	var CHAR_JITTER = 45;
	var LINE_PAUSE_MS = 650;   // وقفة عند آخر السطر (زي ما بنفكّر قبل السطر اللي بعده)
	var NOTE_PAUSE_MS = 1400;  // وقفة أطول لما يظهر شرح
	var OUTPUT_DELAY_MS = 900;
	var HOLD_MS = 9000;        // الكود كامل + النتيجة بيفضلوا ٩ ثواني قبل المثال اللي بعده

	// كل مثال: سطور، وكل سطر مقاطع [class, text] + شرح اختياري بيظهر لما السطر يخلص.
	// الفئات بتتطابق مع assets/home/style.css: k=keyword · t=type · s=string · c=comment · f=function · n=number
	var snippets = [
		{
			id: 'java',
			file: 'Grade.java',
			out: '<b>▶ درجتك: A ✓</b> <span>مبروك، نجحت من أول محاولة</span>',
			lines: [
				{ code: [['k', 'int'], ['', ' score = '], ['n', '92'], ['', ';']], note: 'متغيّر: صندوق بنحط فيه رقم ونديله اسم.' },
				{ code: [['k', 'String'], ['', ' grade;']] },
				{ code: [['k', 'if'], ['', ' (score >= '], ['n', '90'], ['', ') {']], note: 'الشرط: «لو» الدرجة ٩٠ أو أكتر… نفس منطقك في الحياة.' },
				{ code: [['', '    grade = '], ['s', '"A"'], ['', ';']] },
				{ code: [['', '} '], ['k', 'else'], ['', ' {']] },
				{ code: [['', '    grade = '], ['s', '"B"'], ['', ';']] },
				{ code: [['', '}']] },
				{ code: [['', 'System.out.'], ['f', 'println'], ['', '('], ['s', '"درجتك: "'], ['', ' + grade);']], note: 'الطباعة: الكمبيوتر بيرد عليك في التيرمينال تحت.' }
			]
		},
		{
			id: 'oop',
			file: 'Student.java',
			out: '<b>▶ Ahmed · Level 2</b> <span>أول Object ليك اشتغل</span>',
			lines: [
				{ code: [['k', 'public class '], ['t', 'Student'], ['', ' {']], note: 'الكلاس = القالب. الطالب الحقيقي بنعمله منه بعدين.' },
				{ code: [['', '    '], ['k', 'private'], ['', ' String name;']], note: 'private: البيانات مخفية، محدش يعدّلها غير الكلاس نفسه.' },
				{ code: [['', '    '], ['k', 'private int'], ['', ' level;']] },
				{ code: [['', '    '], ['k', 'public '], ['f', 'Student'], ['', '(String name, '], ['k', 'int'], ['', ' level) {']], note: 'الـConstructor: بيتنادى مرة واحدة لما نعمل طالب جديد.' },
				{ code: [['', '        '], ['k', 'this'], ['', '.name = name;  '], ['k', 'this'], ['', '.level = level;']] },
				{ code: [['', '    }']] },
				{ code: [['', '}']] },
				{ code: [['t', 'Student'], ['', ' s = '], ['k', 'new '], ['f', 'Student'], ['', '('], ['s', '"Ahmed"'], ['', ', '], ['n', '2'], ['', ');']], note: 'new: هنا الطالب اتولد فعلًا من القالب.' }
			]
		},
		{
			id: 'cpp',
			file: 'linked_list.cpp',
			out: '<b>▶ 10 → 20 → 30 → NULL</b> <span>أول Linked List بإيدك</span>',
			lines: [
				{ code: [['k', 'struct '], ['t', 'Node'], ['', ' {']], note: 'العقدة: بتشيل رقم، وسهم بيشاور على العقدة اللي بعدها.' },
				{ code: [['', '    '], ['k', 'int'], ['', ' data;']] },
				{ code: [['', '    '], ['t', 'Node'], ['', '* next;']] },
				{ code: [['', '};']] },
				{ code: [['t', 'Node'], ['', '* head = '], ['k', 'new '], ['t', 'Node'], ['', '{'], ['n', '10'], ['', ', '], ['k', 'nullptr'], ['', '};']], note: 'head: أول عقدة، والسهم بتاعها لسه فاضي.' },
				{ code: [['', 'head->next = '], ['k', 'new '], ['t', 'Node'], ['', '{'], ['n', '20'], ['', ', '], ['k', 'nullptr'], ['', '};']] },
				{ code: [['', 'head->next->next = '], ['k', 'new '], ['t', 'Node'], ['', '{'], ['n', '30'], ['', ', '], ['k', 'nullptr'], ['', '};']], note: 'كل عقدة جديدة بنعلّقها في سهم اللي قبلها. دي القائمة كلها.' }
			]
		},
		{
			id: 'dart',
			file: 'main.dart',
			out: '<b>▶ أهلاً يا Ahmed 👋</b> <span>أول خطوة ناحية Flutter</span>',
			lines: [
				{ code: [['k', 'class '], ['t', 'User'], ['', ' {']] },
				{ code: [['', '  '], ['k', 'final'], ['', ' String name;']], note: 'final: قيمة بتتحدد مرة واحدة ومبتتغيرش بعدها.' },
				{ code: [['', '  '], ['f', 'User'], ['', '('], ['k', 'this'], ['', '.name);']], note: 'Dart بيخلّيك تكتب الـConstructor في سطر واحد.' },
				{ code: [['', '  String '], ['f', 'greet'], ['', '() => '], ['s', '\'أهلاً يا $name 👋\''], ['', ';']], note: 'الـ$name: بنحط المتغيّر جوه النص مباشرة.' },
				{ code: [['', '}']] },
				{ code: [['k', 'void '], ['f', 'main'], ['', '() => '], ['f', 'print'], ['', '('], ['t', 'User'], ['', '('], ['s', '\'Ahmed\''], ['', ').greet());']] }
			]
		},
		{
			id: 'python',
			file: 'projects.py',
			out: '<b>▶ 3 مشاريع · ابدأ مجانًا</b> <span>البوابة المجانية</span>',
			lines: [
				{ code: [['', 'projects = ['], ['s', '"آلة حاسبة"'], ['', ', '], ['s', '"لعبة تخمين"'], ['', ', '], ['s', '"مدير مهام"'], ['', ']']], note: 'القائمة: بنجمع أكتر من قيمة في مكان واحد.' },
				{ code: [['k', 'for'], ['', ' p '], ['k', 'in'], ['', ' projects:']], note: 'الحلقة: نفس الأمر بيتكرر على كل عنصر.' },
				{ code: [['', '    '], ['f', 'print'], ['', '('], ['s', 'f"✓ {p}"'], ['', ')']] },
				{ code: [['f', 'print'], ['', '('], ['f', 'len'], ['', '(projects), '], ['s', '"مشاريع · ابدأ مجانًا"'], ['', ')']] }
			]
		}
	];

	function escapeHtml(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function lineText(line) {
		var t = '';
		for (var i = 0; i < line.code.length; i++) {
			t += line.code[i][1];
		}
		return t;
	}

	// يرندر سطر لحد حرف معيّن (upto = عدد الحروف)، أو كامل لو upto = null
	function renderLine(line, upto) {
		var html = '';
		var count = 0;
		for (var i = 0; i < line.code.length; i++) {
			var cls = line.code[i][0];
			var txt = line.code[i][1];
			var take = upto === null ? txt.length : Math.max(0, Math.min(txt.length, upto - count));
			count += txt.length;
			var piece = escapeHtml(txt.slice(0, take));
			html += cls ? '<span class="lsh-code__' + cls + '">' + piece + '</span>' : piece;
			if (take < txt.length) {
				break;
			}
		}
		return html;
	}

	function renderFull(snippet) {
		var parts = [];
		for (var i = 0; i < snippet.lines.length; i++) {
			parts.push(renderLine(snippet.lines[i], null));
		}
		return parts.join('\n');
	}

	// آخر شرحين بس عشان الكارت ميطولش؛ الأخير بياخد حركة دخول
	function renderNotes(snippet, uptoLine) {
		var items = [];
		for (var i = 0; i <= uptoLine && i < snippet.lines.length; i++) {
			if (snippet.lines[i].note) {
				items.push({ text: snippet.lines[i].note, isNew: i === uptoLine });
			}
		}
		items = items.slice(-2);
		var html = '';
		for (var j = 0; j < items.length; j++) {
			html += '<span class="lsh-code__note' + (items[j].isNew ? ' is-new' : '') + '">' + escapeHtml(items[j].text) + '</span>';
		}
		return html;
	}

	// ── الحالة: حلقة واحدة، وكل حاجة هنا عشان نكمّل من نفس المكان بعد أي توقّف ──
	var state = { index: 0, line: 0, k: 0, phase: 'typing', paused: false };
	var timer = null;

	function schedule(fn, ms) {
		clearTimeout(timer);
		timer = setTimeout(fn, ms);
	}

	function setActiveTab(index) {
		for (var i = 0; i < tabs.length; i++) {
			var on = tabs[i].getAttribute('data-lsh-tab') === snippets[index].id;
			tabs[i].classList.toggle('is-active', on);
			tabs[i].setAttribute('aria-selected', on ? 'true' : 'false');
		}
	}

	function start(index) {
		state.index = index;
		state.line = 0;
		state.k = 0;
		state.phase = 'typing';
		setActiveTab(index);
		out.innerHTML = '<span class="lsh-code__prompt">$</span>';
		notes.innerHTML = '';
		pre.innerHTML = '<span class="lsh-code__cur"></span>';
		if (reduceMotion) {
			pre.innerHTML = renderFull(snippets[index]);
			notes.innerHTML = renderNotes(snippets[index], snippets[index].lines.length - 1);
			out.innerHTML = snippets[index].out;
			state.phase = 'hold';
			return;
		}
		schedule(tick, 400);
	}

	function tick() {
		if (document.hidden || state.paused) {
			return; // بيتكمّل من visibilitychange / mouseleave
		}
		var snippet = snippets[state.index];

		if (state.phase === 'typing') {
			var line = snippet.lines[state.line];
			var full = lineText(line);
			state.k += 1;
			var done = '';
			for (var i = 0; i < state.line; i++) {
				done += renderLine(snippet.lines[i], null) + '\n';
			}
			pre.innerHTML = done + renderLine(line, state.k) + '<span class="lsh-code__cur"></span>';

			if (state.k < full.length) {
				schedule(tick, CHAR_MS + Math.random() * CHAR_JITTER);
				return;
			}
			// السطر خلص
			var pause = LINE_PAUSE_MS;
			if (line.note) {
				notes.innerHTML = renderNotes(snippet, state.line);
				pause = NOTE_PAUSE_MS;
			}
			state.line += 1;
			state.k = 0;
			if (state.line >= snippet.lines.length) {
				state.phase = 'output';
				schedule(tick, OUTPUT_DELAY_MS);
			} else {
				schedule(tick, pause);
			}
			return;
		}

		if (state.phase === 'output') {
			pre.innerHTML = renderFull(snippet);
			out.innerHTML = snippet.out;
			state.phase = 'hold';
			schedule(tick, HOLD_MS);
			return;
		}

		// hold → المثال اللي بعده
		start((state.index + 1) % snippets.length);
	}

	// التابات: الزائر يختار اللغة بنفسه
	for (var t = 0; t < tabs.length; t++) {
		tabs[t].addEventListener('click', function (ev) {
			var id = ev.currentTarget.getAttribute('data-lsh-tab');
			for (var i = 0; i < snippets.length; i++) {
				if (snippets[i].id === id) {
					clearTimeout(timer);
					state.paused = false;
					start(i);
					return;
				}
			}
		});
	}

	// وقّف لما الماوس على الكارت (الزائر بيقرا)، وكمّل لما يمشي
	root.addEventListener('mouseenter', function () {
		state.paused = true;
		clearTimeout(timer);
	});
	root.addEventListener('mouseleave', function () {
		if (!state.paused) {
			return;
		}
		state.paused = false;
		if (!reduceMotion) {
			schedule(tick, 300);
		}
	});

	// وقّف لما التاب يتخفى، وكمّل من نفس الحرف لما يرجع — حلقة واحدة دايمًا
	document.addEventListener('visibilitychange', function () {
		clearTimeout(timer);
		if (!document.hidden && !reduceMotion && !state.paused) {
			schedule(tick, 300);
		}
	});

	start(0);
})();

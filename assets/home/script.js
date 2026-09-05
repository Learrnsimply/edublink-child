/**
 * الرئيسية — assets/home/script.js
 *
 * كارت الكود في الهيرو: بيكتب ٣ أمثلة بالتتابع (Java ← Java OOP ← C++ Linked List)
 * وبيعرض نتيجة كل واحد وبيعيد. لو المتصفح طالب حركة أقل، بيسيب الكود اللي في الـHTML كما هو.
 * الأسئلة الشائعة بـ<details> ومش محتاجة جافاسكريبت.
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
	var fileName = root.querySelector('[data-lsh-file]');
	if (!pre || !out || !fileName) {
		return;
	}

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (reduceMotion) {
		return; // الكود الكامل موجود أصلًا في الـHTML — مفيش حاجة تتحرك
	}

	// كل مقطع: [class, text]. الفئة فاضية = نص عادي. الفئات بتتطابق مع assets/home/style.css
	var snippets = [
		{
			file: 'Main.java',
			out: '<b>▶ أهلاً بيك في اتعلم ببساطة</b>',
			code: [
				['c', '// أول برنامج Java ليك\n'],
				['k', 'public class '], ['t', 'Main'], ['', ' {\n'],
				['', '    '], ['k', 'public static void '], ['f', 'main'], ['', '(String[] args) {\n'],
				['', '        String name = '], ['s', '"اتعلم ببساطة"'], ['', ';\n'],
				['', '        System.out.'], ['f', 'println'], ['', '('], ['s', '"أهلاً بيك في "'], ['', ' + name);\n'],
				['', '    }\n}']
			]
		},
		{
			file: 'Student.java',
			out: '<b>▶ Ahmed · Level 2 · OOP</b>',
			code: [
				['c', '// Encapsulation: البيانات مخفية والوصول بـ getters\n'],
				['k', 'public class '], ['t', 'Student'], ['', ' {\n'],
				['', '    '], ['k', 'private '], ['t', 'String'], ['', ' name;\n'],
				['', '    '], ['k', 'private int'], ['', ' level;\n\n'],
				['', '    '], ['k', 'public '], ['f', 'Student'], ['', '(String name, int level) {\n'],
				['', '        '], ['k', 'this'], ['', '.name = name; '], ['k', 'this'], ['', '.level = level;\n'],
				['', '    }\n'],
				['', '    '], ['k', 'public '], ['t', 'String'], ['', ' '], ['f', 'getName'], ['', '() { '], ['k', 'return'], ['', ' name; }\n}']
			]
		},
		{
			file: 'linked_list.cpp',
			out: '<b>▶ 10 → 20 → 30 → NULL</b>',
			code: [
				['c', '// أول Linked List بإيدك\n'],
				['k', 'struct '], ['t', 'Node'], ['', ' {\n'],
				['', '    '], ['k', 'int'], ['', ' data;\n'],
				['', '    '], ['t', 'Node'], ['', '* next;\n};\n\n'],
				['t', 'Node'], ['', '* head = '], ['k', 'new '], ['t', 'Node'], ['', '{10, '], ['k', 'nullptr'], ['', '};\n'],
				['', 'head->next = '], ['k', 'new '], ['t', 'Node'], ['', '{20, '], ['k', 'nullptr'], ['', '};\n'],
				['', 'head->next->next = '], ['k', 'new '], ['t', 'Node'], ['', '{30, '], ['k', 'nullptr'], ['', '};']
			]
		}
	];

	function escapeHtml(s) {
		return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function total(snippet) {
		var n = 0;
		for (var i = 0; i < snippet.code.length; i++) {
			n += snippet.code[i][1].length;
		}
		return n;
	}

	function render(snippet, upto) {
		var html = '';
		var count = 0;
		for (var i = 0; i < snippet.code.length; i++) {
			var cls = snippet.code[i][0];
			var txt = snippet.code[i][1];
			var take = Math.max(0, Math.min(txt.length, upto - count));
			count += txt.length;
			var piece = escapeHtml(txt.slice(0, take));
			html += cls ? '<span class="lsh-code__' + cls + '">' + piece + '</span>' : piece;
			if (take < txt.length) {
				break;
			}
		}
		return html;
	}

	var index = 0;
	var timer = null;

	function play() {
		var snippet = snippets[index];
		var n = total(snippet);
		var k = 0;
		fileName.textContent = snippet.file;
		out.innerHTML = '<span style="color:var(--lsh-dim)">$ </span>';

		function step() {
			k += 1;
			pre.innerHTML = render(snippet, k) + '<span class="lsh-code__cur"></span>';
			if (k < n) {
				timer = setTimeout(step, 18 + Math.random() * 30);
			} else {
				timer = setTimeout(function () {
					out.innerHTML = snippet.out;
					timer = setTimeout(function () {
						index = (index + 1) % snippets.length;
						play();
					}, 3800);
				}, 500);
			}
		}
		step();
	}

	// وقّف الكتابة لما التاب يبقى مخفي — توفير للبطارية ومفيش لزمة تكتب لمحدش
	document.addEventListener('visibilitychange', function () {
		if (document.hidden) {
			clearTimeout(timer);
		} else {
			play();
		}
	});

	play();
})();

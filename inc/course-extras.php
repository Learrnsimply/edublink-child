<?php
/**
 * كروت «هتبني إيه» والأسئلة الشائعة لكل كورس.
 *
 * اتنقلت من tutor/single-course.php زي ما هي (٥ سبتمبر ٢٠٢٦) عشان الكونترولر يبقى
 * بيانات وترتيب بس. المفتاح سلاج الكورس (post_name) بعد فك الترميز — ووردبريس بيخزّن
 * السلاج العربي مشفّر. الكورس اللي مش في الخريطة بياخد محتوى Dart كافتراضي.
 *
 * الخطوة الجاية في MOSH-BLUEPRINT: نقل الكلام ده لحقول في Tutor بدل الكود.
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $slug  Course slug (post_name).
 * @return array{build_items: array, faq_items: array}
 *   build_items[].kind = 'code' | 'oop' | 'flutter' | 'backend' | 'mobile' | 'python'
 *   build_items[].tag  = small label shown above the card title
 *   build_items[].title, .desc = card body
 *   faq_items[].q, .a   = question and answer
 */
function learnsimply_get_course_extras( $slug ) {
	$map = array(

		// ───────────────────────────────────────────────
		// DART — Flutter / Mobile focus
		// ───────────────────────────────────────────────
		'dart' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">void</span> main() {\n  print(<span class="lvc-str">\'Hello, Dart!\'</span>);\n  <span class="lvc-kw">for</span> (<span class="lvc-kw">var</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n    print(i);\n  }\n}',
					'tag'   => 'تطبيقي',
					'title' => 'أول Dart app ليك',
					'desc'  => 'هنكتب أول برنامج ليك من الصفر — variables, loops, functions.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'هنبني class كامل بـ inheritance و polymorphism — وهنفهم ليه OOP مهم قبل ما تدخل Flutter.',
				),
				array(
					'kind'  => 'flutter',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Flutter',
					'desc'  => 'لما تخلص الكورس، هتكون جاهز تبدأ رحلتك في Flutter — ودي الخطوة الطبيعية الجاية.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه، الكورس معمول للمبتدئين تماماً. بنبدأ من "إيه هو البرمجة" ونوصل لحد إنك تبني تطبيقات Dart كاملة. مش محتاج أي خلفية برمجية.',
				),
				array(
					'q' => 'إيه الفرق بين الكورس ده وكورسات Java اللي عندكم؟',
					'a' => 'Java للتطبيقات العامة (Backend, Android Enterprise, Big Data). Dart/Flutter للموبايل والويب الحديث. لو هدفك الموبايل، ابدأ بـ Dart. لو هدفك Backend، ابدأ بـ Java. الاتنين في الباقة لو حابب تجرب الاتنين.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس بيتحدث بشكل دوري مع كل إصدار جديد من Dart. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة، من غير أي مصاريف إضافية.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب — وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

		// ───────────────────────────────────────────────
		// JAVA — Backend / general-purpose focus
		// ───────────────────────────────────────────────
		'java-course-level1' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">public class</span> Main {\n  <span class="lvc-kw">public static void</span> main(String[] args) {\n    System.out.<span class="lvc-kw">println</span>(<span class="lvc-str">"Hello, Java!"</span>);\n    <span class="lvc-kw">for</span> (<span class="lvc-kw">int</span> i = <span class="lvc-num">0</span>; i &lt; <span class="lvc-num">5</span>; i++) {\n      System.out.<span class="lvc-kw">println</span>(i);\n    }\n  }\n}',
					'tag'   => 'تطبيقي',
					'title' => 'أول Java app ليك',
					'desc'  => 'هنكتب أول Java program من الصفر — variables, loops, methods, الـ main class.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'Class + Inheritance',
					'desc'  => 'Java = لغة OOP من الطراز الأول. هنغطي encapsulation, inheritance, polymorphism, interfaces بالتفصيل.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Backend',
					'desc'  => 'لما تخلص الكورس، هتكون جاهز تتعلم Spring Boot أو أي framework تاني.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه، الكورس معمول للمبتدئين تماماً. Java لغة ممتازة كأول لغة لأنها بتعلمك الـ fundamentals بشكل صارم.',
				),
				array(
					'q' => 'إيه اللي أقدر أعمله بعد ما أخلص الكورس؟',
					'a' => 'تقدر تتقدم لشغل Junior Java Developer، تتعلم Spring Boot للـ Backend، أو تدخل مجال الـ Android بـ Java.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس بيتحدث بشكل دوري مع كل إصدار جديد من Java. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب — وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

		// ───────────────────────────────────────────────

		// ───────────────────────────────────────────────
		// JAVA OOP — Object-Oriented Programming focus
		// ───────────────────────────────────────────────
		'javaoop' => array(
			'build_items' => array(
				array(
					'kind'  => 'oop',
					'tag'   => 'OOP',
					'title' => 'تعلم OOP Java',
					'desc'  => 'هتفهم الـ classes, objects, inheritance, polymorphism, encapsulation — كل اللي محتاجه عشان تبني Java code محترف.',
				),
				array(
					'kind'  => 'python',  // projects card style (gradient icon)
					'tag'   => 'مشاريع',
					'title' => 'مشاريع OOP',
					'desc'  => 'مشاريع تطبيقية بتحاكي مشاكل حقيقية — هنبني banking system, employee management, game characters.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'الخطوة التانية',
					'title' => 'جاهز للـ Backend',
					'desc'  => 'بعد ما تتقن OOP، هتكون جاهز تتعلم Spring Boot أو تدخل مجال الـ software development بشكل احترافي.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل لازم أكون خلصت كورس Java الأساسي قبل ما أبدأ OOP؟',
					'a' => 'الأفضل تكون عارف أساسيات Java (variables, loops, methods, if/else). لو مش عارفهم، كورس "جافا للمبتدئين" هيكون أحسن بداية ليك.',
				),
				array(
					'q' => 'استرداد الفلوس خلال 7 أيام من شراء الكورس — إزاي بيشتغل؟',
					'a' => 'بعد ما تشترك في الكورس، عندك 7 أيام كاملة تجربه براحتك. لو خلالهم حسيت إن الكورس مش مناسبك لأي سبب، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة أو شروط معقدة. كل اللي محتاجه رسالة واحدة وبس.',
				),
				array(
					'q' => 'هل الكورس ده نظري ولا تطبيقي؟',
					'a' => 'الكورس تطبيقي 100%. كل درس بينتهي بمشروع صغير أو تمرين تطبقه بنفسك. مش هنقعد نقرأ theory بس — هنبني حاجات فعلية.',
				),
				array(
					'q' => 'إيه اللي يقدر يعمله بعد الكورس ده؟',
					'a' => 'هتقدر تتقدم لشغل Junior Backend Developer، تكمل على Spring Boot، أو تاخد أي interview OOP-related بثقة.',
				),
				array(
					'q' => 'هل الكورس بيتجدد؟ ولو اشتريت، هاخد التحديثات ببلاش؟',
					'a' => 'أيوه، الكورس يتحدث مع كل إصدار جديد من Java. أي تحديث بنضيفه بيكون متاح لكل اللي اشتروا الكورس — مدى الحياة، من غير أي مصاريف إضافية.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي عملياً؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا من خلال صفحة "تواصل معنا" أو الإيميل، وهنرد عليك في أقل من 24 ساعة ونرجعلك فلوسك بالكامل.',
				),
			),
		),
		// PYTHON PROJECTS — Free / project-focused
		// ───────────────────────────────────────────────
		'مشاريع-بايثون-للمبتدئين' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">def</span> greet(name):\n    <span class="lvc-kw">return</span> <span class="lvc-str">f\'Hello, {name}!\'</span>\n\n<span class="lvc-kw">for</span> i <span class="lvc-kw">in</span> <span class="lvc-kw">range</span>(<span class="lvc-num">5</span>):\n    print(greet(<span class="lvc-str">f\'World {i}\'</span>))',
					'tag'   => 'تطبيقي',
					'title' => 'أول Python script',
					'desc'  => 'هنكتب أول Python script من الصفر — functions, loops, string formatting.',
				),
				array(
					'kind'  => 'python',
					'tag'   => 'مشاريع',
					'title' => 'مشاريع حقيقية',
					'desc'  => 'مشاريع تطبيقية بتحاكي مشاكل حقيقية — to-do list, calculator, simple game.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'جاهزية',
					'title' => 'مستعد لـ Flask / Django',
					'desc'  => 'لما تخلص، هتكون جاهز تتعلم أي web framework أو حتى تدخل مجال الـ data science.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما برمجت قبل كده؟',
					'a' => 'أيوه! Python هي أسهل لغة تبدأ بيها. الكورس معمول خصيصاً للمبتدئين — هنبدأ من الصفر.',
				),
				array(
					'q' => 'إيه الفرق بين الكورس ده وباقي كورسات Python على اليوتيوب؟',
					'a' => 'الفرق إن الكورس ده مبني على مشاريع تطبيقية. مش بس syntax — كل درس بينتهي بمشروع صغير تضيفه للـ Portfolio بتاعك.',
				),
				array(
					'q' => 'هل الكورس مجاني فعلاً؟',
					'a' => 'أيوه، الكورس مجاني تماماً. من غير اشتراك، من غير بطاقة ائتمان. كل اللي محتاجه إنك تسجل في الموقع.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب.',
				),
				array(
					'q' => 'إيه الكورس المناسب اللي بعده؟',
					'a' => 'بعد ما تخلص، أنصحك بـ "أساسيات Dart" لو هدفك الموبايل، أو "جافا" لو هدفك Backend متكامل.',
				),
			),
		),

		// ───────────────────────────────────────────────
		// DATA STRUCTURE — المستوى الأول (arrays → linked list → stack → queue)
		// ───────────────────────────────────────────────
		'data-structure-c' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">struct</span> Node {\n    <span class="lvc-kw">int</span> data;\n    <span class="lvc-kw">struct</span> Node* next;\n};\n\n<span class="lvc-cmt">// أول linked list بإيدك</span>\nhead-&gt;next = newNode;',
					'tag'   => 'تطبيقي',
					'title' => 'أول Linked List بإيدك',
					'desc'  => 'هنبدأ من المصفوفة ومشاكلها، وبعدين نبني linked list من الصفر بـ struct و pointers — insert و delete و display خطوة بخطوة.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'هياكل',
					'title' => 'Stack + Queue',
					'desc'  => 'هنعمل الـ stack والـ queue بطريقتين — بالمصفوفة وبالـ linked list — مع push و pop و enqueue و dequeue و peek كاملين.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'جاهزية',
					'title' => 'مستعد للمستوى التاني',
					'desc'  => 'لما تخلص، هتكون فاهم الـ pointers والـ double linked list كويس — وجاهز تدخل على الـ trees والـ BST في المستوى التاني.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'هل الكورس ده مناسب لو عمري ما اتعاملت مع هياكل البيانات قبل كده؟',
					'a' => 'أيوه. بنبدأ من "ما هي هياكل البيانات" و"ليه محتاجين المصفوفة" ونمشي بالتدريج لحد الـ double linked list. مش محتاج أي خلفية في الموضوع.',
				),
				array(
					'q' => 'محتاج أعرف إيه قبل ما أبدأ الكورس؟',
					'a' => 'محتاج بس أساسيات البرمجة — المتغيرات، الشروط، اللوبات، والدوال. الكورس بيشرح المصفوفات والمؤشرات (pointers) من الصفر جواه.',
				),
				array(
					'q' => 'إيه اللي هتغطيه بالظبط في المستوى الأول؟',
					'a' => 'المصفوفات والمؤشرات، الـ linked list (single و double) بكل عملياتها، الـ stack بالمصفوفة وبالـ linked list، والـ queue بالمصفوفة وبالـ linked list — 76 درس تطبيقي.',
				),
				array(
					'q' => 'الكورس نظري ولا فيه كود فعلي؟',
					'a' => 'كل عملية بنكتبها كود كامل قدامك ونتتبعها خطوة بخطوة — insert node، delete node، display، push، pop، enqueue، dequeue. مش شرح نظري على السبورة.',
				),
				array(
					'q' => 'لو مش فهمت درس، فيه دعم؟',
					'a' => 'أكيد. أي سؤال يجيلك، تقدر تبعت من خلال جروب الـ Telegram المخصص للطلاب — وغالباً بيرد عليك المدرب نفسه (أحمد) أو حد من المساعدين في أقل من 24 ساعة.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

		// ───────────────────────────────────────────────
		// DATA STRUCTURE — المستوى الثاني (circular, stack apps, trees, BST)
		// ───────────────────────────────────────────────
		'data_structure_level2' => array(
			'build_items' => array(
				array(
					'kind'  => 'code',
					'code'  => '<span class="lvc-kw">if</span> (value &lt; root-&gt;data)\n    root-&gt;left  = insert(root-&gt;left, value);\n<span class="lvc-kw">else</span>\n    root-&gt;right = insert(root-&gt;right, value);\n\n<span class="lvc-cmt">// Binary Search Tree</span>',
					'tag'   => 'تطبيقي',
					'title' => 'Binary Search Tree كامل',
					'desc'  => 'هنبني BST من الصفر — create node، insert، search، find min و max، وdelete بكل حالاتها.',
				),
				array(
					'kind'  => 'oop',
					'tag'   => 'أشجار',
					'title' => 'الشجر والـ Traversal',
					'desc'  => 'مصطلحات الشجرة، أنواعها (full، perfect، complete)، والـ traversals الثلاثة — inorder و preorder و postorder بالشرح والكود.',
				),
				array(
					'kind'  => 'backend',
					'tag'   => 'مسائل',
					'title' => 'مسائل الـ interviews',
					'desc'  => 'تطبيقات الـ stack الحقيقية — balanced parentheses، تحويل infix لـ postfix، وحساب الـ postfix expression خطوة بخطوة.',
				),
			),
			'faq_items' => array(
				array(
					'q' => 'لازم أخلص المستوى الأول قبل ما أبدأ ده؟',
					'a' => 'الأفضل أيوه. المستوى ده بيبني على الـ linked list والـ stack والـ queue. فيه مراجعة سريعة عليهم في أول الكورس، بس لو مش عارفهم خالص ابدأ بالمستوى الأول.',
				),
				array(
					'q' => 'إيه الفرق بين المستوى الأول والتاني؟',
					'a' => 'الأول بيغطي الأساسيات: المصفوفات، الـ linked list، الـ stack، والـ queue. التاني بيدخل على الـ circular linked list والـ circular queue، تطبيقات الـ stack، والأشجار بأنواعها والـ Binary Search Tree.',
				),
				array(
					'q' => 'إيه اللي هتغطيه بالظبط في المستوى التاني؟',
					'a' => 'circular linked list و circular queue، تطبيقات الـ stack (balanced parentheses و infix/postfix/prefix)، مصطلحات الشجر وأنواعه، الـ traversals، والـ Binary Search Tree كامل — 87 درس.',
				),
				array(
					'q' => 'الكورس ده هيفيدني في الـ interviews؟',
					'a' => 'أيوه. أسئلة الـ trees والـ BST والـ traversals وتحويل الـ expressions من أكتر الأسئلة اللي بتتسأل في interviews الشركات — والكورس بيغطيها بالكود مش بالنظري.',
				),
				array(
					'q' => 'فيه تمارين وواجبات؟',
					'a' => 'أيوه. فيه أسئلة على أنواع الشجر، اختبار على المصطلحات، حل واجب، وأمثلة متعددة على infix to postfix — كل جزء بينتهي بتطبيق.',
				),
				array(
					'q' => 'ضمان استرداد الفلوس شغال إزاي؟',
					'a' => 'لو خلال أول 7 أيام من الاشتراك حسيت إن الكورس مش مناسبك، ابعتلنا وهنرجعلك فلوسك بالكامل — من غير أي أسئلة.',
				),
			),
		),

	);

	// Fallback: use the Dart content for any course we haven't mapped yet.
	// This way a new course never shows wrong content — it shows the most
	// useful default. You can add a real entry above for each new course.
	$fallback = isset( $map['dart'] ) ? $map['dart'] : array(
		'build_items' => array(),
		'faq_items'   => array(),
	);

	$extras = isset( $map[ $slug ] ) ? $map[ $slug ] : $fallback;

	// The 'code' snippets above are single-quoted, so their \n stays a literal
	// backslash-n. Turn it into a real line break so the <pre> mockup renders
	// the snippet on multiple lines instead of one long line.
	if ( ! empty( $extras['build_items'] ) ) {
		foreach ( $extras['build_items'] as $i => $item ) {
			if ( isset( $item['code'] ) ) {
				$extras['build_items'][ $i ]['code'] = str_replace( '\n', "\n", $item['code'] );
			}
		}
	}

	return $extras;
}

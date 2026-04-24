<?php
/**
 * Idempotent content and site bootstrap for Kepoli.
 */

require_once __DIR__ . '/version.php';

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_seed_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : trim((string) $value);
}

function kepoli_seed_json(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("Cannot read {$path}");
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON in {$path}: " . json_last_error_msg());
    }

    return $data;
}

function kepoli_seed_slug_to_title(string $slug): string
{
    return ucwords(str_replace('-', ' ', $slug));
}

function kepoli_seed_duration_minutes(string $value): int
{
    $minutes = 0;
    if (preg_match('/(\d+)\s*ora/', $value, $matches)) {
        $minutes += ((int) $matches[1]) * 60;
    }
    if (preg_match('/(\d+)\s*min/', $value, $matches)) {
        $minutes += (int) $matches[1];
    }
    return max(1, $minutes);
}

function kepoli_seed_iso_duration(string $value): string
{
    $minutes = kepoli_seed_duration_minutes($value);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    $duration = 'PT';
    if ($hours > 0) {
        $duration .= $hours . 'H';
    }
    if ($mins > 0) {
        $duration .= $mins . 'M';
    }
    return $duration;
}

function kepoli_seed_upsert_page(array $page, int $author_id): int
{
    $existing = get_page_by_path($page['slug'], OBJECT, 'page');
    $postarr = [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => $author_id,
        'post_name' => $page['slug'],
        'post_title' => $page['title'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_content' => str_replace(
            ['{{SITE_EMAIL}}', '{{WRITER_EMAIL}}'],
            [kepoli_seed_env('SITE_EMAIL', 'contact@kepoli.com'), kepoli_seed_env('WRITER_EMAIL', 'isalunemerovik@gmail.com')],
            $page['content']
        ),
    ];

    if ($existing) {
        $postarr['ID'] = $existing->ID;
    }

    $id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }

    return (int) $id;
}

function kepoli_seed_ensure_category(array $category): int
{
    $term = term_exists($category['slug'], 'category');
    if (!$term) {
        $term = wp_insert_term($category['name'], 'category', [
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
        ]);
    } else {
        wp_update_term((int) $term['term_id'], 'category', [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
        ]);
    }

    if (is_wp_error($term)) {
        throw new RuntimeException($term->get_error_message());
    }

    return (int) $term['term_id'];
}

function kepoli_seed_ensure_author(): int
{
    $email = kepoli_seed_env('WRITER_EMAIL', 'isalunemerovik@gmail.com');
    $username = 'isalune-merovik';
    $user = get_user_by('email', $email);

    if (!$user) {
        $user_id = username_exists($username);
        if (!$user_id) {
            $user_id = wp_create_user($username, wp_generate_password(32, true), $email);
        }
        $user = get_user_by('id', $user_id);
    }

    if (!$user || is_wp_error($user)) {
        throw new RuntimeException('Could not create author user.');
    }

    wp_update_user([
        'ID' => $user->ID,
        'display_name' => 'Isalune Merovik',
        'nickname' => 'Isalune Merovik',
        'first_name' => 'Isalune',
        'last_name' => 'Merovik',
        'user_url' => home_url('/despre-autor/'),
        'description' => 'Autoare Kepoli. Scrie retete romanesti, articole culinare si ghiduri practice pentru gatit acasa.',
        'role' => 'administrator',
    ]);

    return (int) $user->ID;
}

function kepoli_seed_link(string $slug, array $post_ids): string
{
    return isset($post_ids[$slug]) ? get_permalink($post_ids[$slug]) : home_url('/');
}

function kepoli_seed_post_data(string $slug, array $posts): ?array
{
    foreach ($posts as $post) {
        if (($post['slug'] ?? '') === $slug) {
            return $post;
        }
    }

    return null;
}

function kepoli_seed_post_title(string $slug, array $posts): string
{
    $post = kepoli_seed_post_data($slug, $posts);
    return $post['title'] ?? kepoli_seed_slug_to_title($slug);
}

function kepoli_seed_post_excerpt(string $slug, array $posts): string
{
    $post = kepoli_seed_post_data($slug, $posts);
    return trim((string) ($post['excerpt'] ?? ''));
}

function kepoli_seed_post_intro(array $post): string
{
    return trim((string) ($post['intro'] ?? $post['excerpt'] ?? ''));
}

function kepoli_seed_recipe_matches(array $post, array $needles): bool
{
    $haystack = strtolower(
        implode(' ', array_filter([
            $post['slug'] ?? '',
            $post['title'] ?? '',
            $post['excerpt'] ?? '',
            $post['notes'] ?? '',
            implode(' ', $post['ingredients'] ?? []),
        ]))
    );

    foreach ($needles as $needle) {
        if (str_contains($haystack, strtolower($needle))) {
            return true;
        }
    }

    return false;
}

function kepoli_seed_recipe_value_paragraphs(array $post): array
{
    switch ($post['category']) {
        case 'ciorbe-si-supe':
            return [
                'Reteta aceasta raspunde bine unei cautari simple: cum faci acasa un bol romanesc cald, bine legat si usor de servit in familie. Are pasi accesibili, dar si suficiente repere ca sa poti controla limpezimea, aciditatea si textura finala.',
                'Daca gatesti pentru pranz, pentru doua zile sau pentru o masa de duminica, tipul acesta de reteta castiga prin echilibru: ingredientele sunt recognoscibile, timpul de lucru este clar, iar rezultatul poate fi adaptat usor dupa gustul casei.',
            ];
        case 'feluri-principale':
            return [
                'Reteta este construita pentru mesele la care vrei ceva satios, clar explicat si usor de legat de o garnitura sau de o ciorba. Fiecare pas conteaza pentru gustul final, dar nu ai nevoie de tehnici complicate ca sa iasa bine.',
                'Pentru cine cauta mancare romaneasca facuta acasa, avantajul real este predictibilitatea: stii cand sa rumenesti, cand sa adaugi lichidul si ce semne iti arata ca preparatul este gata, nu doar cat timp a stat pe foc.',
            ];
        case 'patiserie-si-deserturi':
            return [
                'Reteta merita daca vrei un desert romanesc explicat fara scurtaturi confuze. Accentul cade pe textura, pe timpii de odihna sau coacere si pe semnele vizuale care iti spun cand preparatul este reusit.',
                'La deserturi, cititorii cauta de obicei doua lucruri: sa nu rateze textura si sa poata repeta rezultatul. Tocmai de aceea, pasii si sfaturile sunt gandite sa reduca micile erori care strica aluaturile ori deserturile prajite.',
            ];
        case 'conserve-si-garnituri':
            return [
                'Reteta este utila cand vrei ceva care completeaza bine masa si poate fi pregatit organizat, cu atentie la curatenie, textura si echilibru de gust. Nu e doar o lista de pasi, ci un mod de lucru usor de urmat de la un sezon la altul.',
                'Pentru garnituri si conserve, valoarea vine din detalii: ce legume alegi, cat lichid lasi, cand gusti si cum pastrezi rezultatul. Tocmai acele detalii fac diferenta intre un preparat bun si unul pe care vrei sa-l refaci sigur.',
            ];
        default:
            return [
                'Reteta este scrisa pentru gatit acasa, cu explicatii clare si repere practice care te ajuta sa intelegi nu doar ordinea pasilor, ci si ce urmaresti in fiecare etapa.',
                'Scopul nu este sa incarce pagina cu text, ci sa raspunda intrebarilor firesti pe care le ai in timp ce gatesti: cat de mult fierbi, cand ajustezi gustul si cum servesti preparatul astfel incat sa ramana echilibrat.',
            ];
    }
}

function kepoli_seed_recipe_ingredient_focus_paragraphs(array $post): array
{
    if (kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])) {
        return [
            'Ingredientele-cheie ale retetei sunt baza lichida, partea grasa care rotunjeste gustul si elementul de legatura care da textura finala. Cand folosesti smantana sau galbenusuri, temperatura devine la fel de importanta ca proportiile.',
            'Daca trebuie sa adaptezi, mergi pe schimbari mici: ajusteaza aciditatea la final, rareste cu zeama fierbinte daca textura e prea densa si evita inlocuirile agresive care schimba prea mult gustul specific retetei.',
        ];
    }

    if (kepoli_seed_recipe_matches($post, ['bors', 'otet', 'lamaie', 'murata'])) {
        return [
            'Aici conteaza mai ales echilibrul dintre ingredientul principal, legumele de baza si sursa de aciditate. Gustul bun nu vine din mult bors sau mult otet, ci din felul in care completeaza restul preparatului fara sa il acopere.',
            'Daca schimbi ingredientele, pastreaza raportul dintre dulceata naturala a legumelor, sarea din preparat si nota acra de la final. Ajustarile mici, facute dupa gust, sunt mai sigure decat o corectie mare dintr-o singura miscare.',
        ];
    }

    if (kepoli_seed_recipe_matches($post, ['drojdie', 'faina', 'aluat', 'gris'])) {
        return [
            'In retetele de aluat sau compozitii sensibile, ingredientele-cheie sunt cele care controleaza structura: faina ori grisul, partea lichida si temperatura la care lucrezi. Cantitatea corecta este importanta, dar si ritmul in care incorporezi ingredientele.',
            'Pentru adaptari, foloseste repere vizuale: aluatul trebuie sa fie elastic sau moale, nu intamplator lipicios, iar compozitia trebuie lasata sa se aseze inainte de modelare, prajire sau coacere.',
        ];
    }

    if (kepoli_seed_recipe_matches($post, ['fasole', 'orez'])) {
        return [
            'Ingredientul principal are nevoie de suficient timp si de lichidul potrivit ca sa se gateasca uniform. In astfel de retete, rabdarea si ordinea etapelor sunt mai importante decat focul mare sau interventiile dese.',
            'Daca adaptezi reteta, tine cont de faptul ca boabele, orezul sau legumele absorb diferit in functie de soi si sezon. Corecteaza consistenta treptat si foloseste lichid fierbinte, nu rece, cand mai completezi.',
        ];
    }

    return [
        'Ingredientele-cheie ale retetei sunt cele care dau corp, gust si ritm prepararii. Merita sa le alegi proaspete si sa le pregatesti din timp, pentru ca ordinea in care intra in vas influenteaza textura mai mult decat pare la prima vedere.',
        'Daca ai nevoie de adaptari, incearca sa schimbi un singur lucru o data: tipul de carne, gradul de grasime, o garnitura sau un condiment. Asa iti ramane clar ce a schimbat cu adevarat rezultatul final.',
    ];
}

function kepoli_seed_recipe_common_mistakes(array $post): array
{
    $items = [];

    if ($post['category'] === 'ciorbe-si-supe') {
        $items[] = 'Fierberea prea agresiva dupa ce ai adaugat ingredientele sensibile poate tulbura zeama si poate rupe textura ingredientelor.';
    }

    if (kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])) {
        $items[] = 'Adaugarea directa a smantanii sau a galbenusurilor in lichid foarte fierbinte fara temperare risca sa taie preparatul.';
    }

    if (kepoli_seed_recipe_matches($post, ['drojdie', 'faina', 'aluat'])) {
        $items[] = 'Prea multa faina adaugata din graba face aluatul greu si ascunde textura pe care o cauti de fapt.';
    }

    if (kepoli_seed_recipe_matches($post, ['orez'])) {
        $items[] = 'Umplerea prea stransa sau lipsa lichidului suficient nu lasa orezul sa se gateasca uniform si sa ramana placut la interior.';
    }

    if (kepoli_seed_recipe_matches($post, ['usturoi'])) {
        $items[] = 'Usturoiul tinut prea mult pe foc amaraste repede si schimba profilul retetei mai mult decat iti doresti.';
    }

    if (kepoli_seed_recipe_matches($post, ['fasole'])) {
        $items[] = 'Sarea pusa prea devreme sau lipsa timpului de inmuiere pot intarzia gatirea boabelor si pot lasa textura neuniforma.';
    }

    $items[] = 'Condimentarea finala facuta fara gustare pe parcurs duce des la preparate bune tehnic, dar plate sau prea insistente la masa.';

    return array_slice(array_values(array_unique($items)), 0, 4);
}

function kepoli_seed_render_related_links(string $heading, string $intro, array $slugs, array $post_ids, array $posts, string $heading_id = ''): string
{
    if ($slugs === []) {
        return '';
    }

    $id_attr = $heading_id !== '' ? ' id="' . esc_attr($heading_id) . '"' : '';
    $html = '<section class="related-posts"><h2' . $id_attr . '>' . esc_html($heading) . '</h2>';
    $html .= '<p>' . esc_html($intro) . '</p><ul>';

    foreach ($slugs as $slug) {
        $title = kepoli_seed_post_title($slug, $posts);
        $excerpt = kepoli_seed_post_excerpt($slug, $posts);
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html($title) . '</a>';
        if ($excerpt !== '') {
            $html .= ' - ' . esc_html(wp_trim_words($excerpt, 20, '...'));
        }
        $html .= '</li>';
    }

    $html .= '</ul></section>';

    return $html;
}

function kepoli_seed_article_context_paragraphs(array $post): array
{
    return [
        'Ghidul de fata este gandit pentru cautari practice, nu pentru formulare vagi. El leaga subiectul principal de deciziile reale din bucatarie: cum alegi, cum organizezi, ce urmaresti si ce poti aplica imediat dupa lectura.',
        'Daca vrei sa folosesti rapid informatia, porneste de la subtitluri si revino la partea care te intereseaza chiar in momentul in care faci lista de cumparaturi, pregatesti ingredientele sau planifici o masa completa.',
    ];
}

function kepoli_seed_article_wrapup(array $post): string
{
    $first = $post['takeaways'][0] ?? 'Porneste de la lucrurile care iti aduc claritate imediata.';
    $second = $post['takeaways'][1] ?? 'Apoi adapteaza sfaturile la ingredientele si ritmul casei tale.';

    return 'Daca vrei sa aplici rapid ideile din ghid, incepe cu doua miscari simple: ' . $first . ' ' . $second . ' In felul acesta, informatia nu ramane doar teorie, ci se transforma mai usor in mese mai bine gandite si retete mai previzibile.';
}

function kepoli_seed_article_snapshot_meta(array $post): array
{
    return [
        'takeaways' => array_values(array_slice($post['takeaways'] ?? [], 0, 3)),
        'section_headings' => array_values(array_map(static function ($section) {
            return (string) ($section['heading'] ?? '');
        }, $post['sections'] ?? [])),
        'section_count' => count($post['sections'] ?? []),
        'faq_count' => count($post['faq'] ?? []),
        'related_recipe_count' => count($post['related'] ?? []),
    ];
}

function kepoli_seed_recipe_intro_guidance(array $post): array
{
    switch ($post['category']) {
        case 'ciorbe-si-supe':
            return [
                'Citeste reteta de la inceput pana la sfarsit si pregateste legumele, verdeata si ingredientele acide inainte sa pornesti focul. La ciorbe si supe, ordinea in care intra ingredientele schimba textura finala mai mult decat pare.',
                'Pastreaza focul domol in etapele sensibile, mai ales cand ai perisoare, dreseala cu smantana sau galuste. O fierbere blanda iti da zeama mai limpede, ingrediente intregi si un gust mai rotund.',
            ];
        case 'feluri-principale':
            return [
                'Pregateste toate ingredientele tocate si masurate inainte de rumenire, pentru ca felurile principale merg bine cand lucrezi fara pauze lungi intre pasi.',
                'Primele minute construiesc baza de gust: nu inghesui tigaia sau oala si lasa ingredientele sa prinda culoare inainte sa adaugi lichidul sau sosul.',
            ];
        case 'patiserie-si-deserturi':
            return [
                'Pentru deserturi si aluaturi, cantareste ingredientele si respecta temperaturile potrivite tipului de preparat. O diferenta mica de faina, lichid sau caldura schimba mult textura finala.',
                'Daca reteta presupune dospire, odihna sau racire, trateaza acel timp ca parte din preparare. Grabirea etapelor duce cel mai des la aluaturi dense sau umpluturi care curg.',
            ];
        case 'conserve-si-garnituri':
            return [
                'Spala bine legumele, ustensilele si recipientele inainte sa incepi. La conserve si garnituri, ordinea si curatenia sunt la fel de importante ca gustul.',
                'Gusta pe parcurs si ajusteaza sarea, aciditatea sau textura inainte de borcanare ori servire. Legumele, soiurile si gradul lor de apa variaza mult de la un sezon la altul.',
            ];
        default:
            return [
                'Pregateste ingredientele din timp si citeste pasii inainte sa incepi, ca sa poti lucra mai calm si mai precis.',
                'Ajusteaza focul si condimentarea treptat, nu dintr-o singura miscare. Rezultatul final iese mai echilibrat cand corectiile sunt mici si atent facute.',
            ];
    }
}

function kepoli_seed_recipe_adjustment_text(array $post): string
{
    if (kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])) {
        return 'Pentru un rezultat mai stabil, tempereaza smantana sau galbenusurile cu lichid cald si evita clocotul puternic dupa ce le-ai adaugat. Asa pastrezi textura fina si reduci riscul de separare.';
    }

    if (kepoli_seed_recipe_matches($post, ['bors', 'otet', 'lamaie', 'murata'])) {
        return 'Aciditatea se ajusteaza cel mai bine la final. Adauga borsul, otetul sau lamaia treptat, gusta dupa fiecare ajustare si opreste-te cand preparatul ramane echilibrat si nu acopera restul aromelor.';
    }

    if (kepoli_seed_recipe_matches($post, ['drojdie', 'faina', 'aluat', 'gris'])) {
        return 'Textura depinde mult de umiditatea fainii, de temperatura ingredientelor si de rabdarea din etapele de odihna. Corecteaza in pasi mici, nu cu adaosuri mari facute dintr-o data.';
    }

    if (kepoli_seed_recipe_matches($post, ['fasole', 'orez'])) {
        return 'Fasolea si orezul cer rabdare si control asupra lichidului. Verifica periodic consistenta si completeaza doar cu lichid fierbinte, ca sa nu intrerupi gatirea si sa nu schimbi brusc textura.';
    }

    if ($post['category'] === 'conserve-si-garnituri') {
        return 'Pentru borcane sau garnituri care trebuie sa reziste, lucreaza cu recipiente curate si nu sari peste pasii de scurgere, sterilizare sau fierbere lenta. Stabilitatea se construieste din rutina, nu din graba.';
    }

    return 'Foloseste nota din reteta ca punct de control, apoi ajusteaza focul, lichidul sau condimentarea in pasi mici. La gatitul de acasa, corectiile mici fac diferenta mai mult decat o schimbare brusca.';
}

function kepoli_seed_recipe_serving_text(array $post): string
{
    switch ($post['category']) {
        case 'ciorbe-si-supe':
            return 'Serveste preparatul bine incalzit, cu verdeata proaspata si ceva simplu alaturi: paine buna, ardei iute sau o garnitura rece care taie din bogatia gustului. Daca planuiesti o masa completa, foloseste recomandarile de la final pentru a lega ciorba de un fel principal sau de un articol util.';
        case 'feluri-principale':
            return 'Felurile principale ies mai bine cand ajung pe masa imediat dupa o odihna scurta. Alege o garnitura simpla sau ceva acru care echilibreaza grasimea ori sosul, apoi completeaza meniul cu sugestiile de la finalul paginii.';
        case 'patiserie-si-deserturi':
            return 'Desertul se serveste cel mai bine dupa ce textura s-a asezat: usor cald, bine racit sau proaspat pudrat, in functie de tipul retetei. Daca pregatesti o masa mai mare, sugestiile din final te ajuta sa-l legi de alte preparate din aceeasi atmosfera.';
        case 'conserve-si-garnituri':
            return 'Serveste preparatul rece sau la temperatura potrivita tipului lui, ca acompaniament pentru mancaruri mai grele ori ca aperitiv simplu. Recomandarile de la finalul paginii te ajuta sa-l pui langa retete care il folosesc natural.';
        default:
            return 'Serveste preparatul intr-un ritm linistit si foloseste recomandarile de la final pentru a-l include intr-o masa mai mare sau intr-un meniu de sezon.';
    }
}

function kepoli_seed_recipe_storage_text(array $post): string
{
    if ($post['slug'] === 'muraturi-asortate') {
        return 'Pastreaza borcanele inchise la loc racoros si ferit de lumina. Dupa deschidere, tine muraturile la frigider, foloseste ustensile curate si asigura-te ca legumele raman acoperite de saramura.';
    }

    if ($post['slug'] === 'zacusca-de-vinete') {
        return 'Borcanele bine sigilate se pastreaza la loc racoros si uscat. Dupa deschidere, tine zacusca la frigider, acoperita, si consuma-o in cateva zile, cu o lingura curata la fiecare servire.';
    }

    if ($post['slug'] === 'salata-de-vinete') {
        return 'Salata de vinete se pastreaza la frigider, in recipient inchis, de obicei una pana la doua zile. Amestec-o usor inainte de servire si evita sa o lasi mult timp la temperatura camerei.';
    }

    if ($post['category'] === 'ciorbe-si-supe') {
        if (kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])) {
            return 'Raceste ciorba in recipiente mai mici si pastreaz-o la frigider pana la doua zile. Reincalzeste-o bland, fara clocot puternic, ca sa nu se taie dreseala.';
        }

        return 'Raceste preparatul in vase joase sau portii mai mici, apoi pastreaza-l la frigider doua-trei zile. La reincalzire, adu-l din nou la fierbere blanda si potriveste gustul abia la final.';
    }

    if ($post['category'] === 'feluri-principale') {
        return 'Pastreaza mancarea la frigider in recipient bine inchis, de regula doua-trei zile, si reincalzeste-o uniform inainte de servire. Daca preparatul are sos, mai adauga putin lichid cald la reincalzire, doar cat sa-si recapete textura.';
    }

    if ($post['category'] === 'patiserie-si-deserturi') {
        if (kepoli_seed_recipe_matches($post, ['prajit', 'gogosi', 'papanasi'])) {
            return 'Deserturile prajite sunt cele mai bune in ziua in care sunt facute. Daca ramane ceva, tine-le acoperite la rece si incalzeste-le usor doar cat sa revina textura exterioara.';
        }

        return 'Pastreaza desertul intr-o cutie bine inchisa, ferita de umezeala, si adapteaza temperatura la tipul lui: la rece pentru umpluturi sensibile, la temperatura camerei pentru aluaturi uscate sau pufoase. Portioneaza doar cat ai nevoie, ca sa nu expui tot preparatul de fiecare data.';
    }

    return 'Pastreaza preparatul in recipiente curate, bine inchise, si adapteaza temperatura de depozitare la ingredientele dominante. Cand ai dubii, raceste mai repede si consuma mai devreme.';
}

function kepoli_seed_recipe_faq(array $post): array
{
    switch ($post['category']) {
        case 'ciorbe-si-supe':
            return [
                [
                    'question' => 'Pot pregati aceasta reteta cu o zi inainte?',
                    'answer' => 'Da. Multe ciorbe si supe se aseaza bine peste noapte, atat timp cat le racesti corect si le pastrezi la frigider. La reincalzire, mergi pe foc mic si ajusteaza gustul dupa ce preparatul este din nou omogen.',
                ],
                [
                    'question' => kepoli_seed_recipe_matches($post, ['smantana', 'galbenus']) ? 'Cum evit sa se taie smantana sau galbenusurile?' : 'Cand ajustez partea acra a retetei?',
                    'answer' => kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])
                        ? 'Tempereaza ingredientele reci cu lichid cald luat din oala si evita clocotul puternic dupa ce le adaugi. Diferenta brusca de temperatura este cauza cea mai comuna a texturii taiate.'
                        : 'Ingredientele acide se potrivesc cel mai bine la finalul gatirii. Adauga-le in etape mici, gusta dupa fiecare pas si lasa preparatul sa mai stea un minut inainte de verdictul final.',
                ],
                [
                    'question' => 'Pot congela reteta?',
                    'answer' => kepoli_seed_recipe_matches($post, ['smantana', 'galbenus'])
                        ? 'Se poate, dar textura dupa decongelare poate fi mai putin fina. Daca vrei cel mai bun rezultat, congeleaza baza fara dreseala si termina reteta la reincalzire.'
                        : 'In general, da, daca ingredientele au fost gatite si racite corect. Imparte in portii, noteaza data si reincalzeste doar cat consumi.',
                ],
            ];
        case 'feluri-principale':
            return [
                [
                    'question' => 'Pot pregati o parte din reteta in avans?',
                    'answer' => 'Da. Tocarea ingredientelor, pregatirea sosului sau chiar gatirea partiala se pot face inainte, iar montajul final devine mai simplu. E util mai ales pentru mesele de familie sau pentru gatit in timpul saptamanii.',
                ],
                [
                    'question' => 'Cum evit sa iasa preparatul uscat sau prea gros?',
                    'answer' => 'Controleaza focul dupa rumenire si urmareste lichidul in etape scurte, nu doar la sfarsit. Daca sosul scade prea repede, completeaza cu lichid cald, iar daca ingredientele sunt foarte slabe, compenseaza prin timp de gatire mai bland.',
                ],
                [
                    'question' => 'Cu ce merge cel mai bine la masa?',
                    'answer' => 'Mamaliga, piureul, painea buna sau muraturile sunt alegeri firesti pentru multe feluri principale romanesti. Cauta in recomandarile de la final combinatiile care sustin gustul retetei, nu il incarca.',
                ],
            ];
        case 'patiserie-si-deserturi':
            return [
                [
                    'question' => 'Pot pregati aluatul sau compozitia in avans?',
                    'answer' => 'De cele mai multe ori, da, dar depinde de reteta. Aluaturile dospite suporta bine o pregatire partiala si o odihna controlata, in timp ce compozitiile foarte aerate sau prajite merg mai bine proaspete.',
                ],
                [
                    'question' => 'De ce nu a iesit textura pufoasa sau frageda?',
                    'answer' => 'Cele mai frecvente cauze sunt ingredientele la temperatura nepotrivita, prea multa faina, dospirea grabita sau focul prea puternic. Corectiile mici, facute la timp, sunt mai utile decat schimbarea retetei din mers.',
                ],
                [
                    'question' => 'Cum il pastrez ca sa ramana bun si a doua zi?',
                    'answer' => 'Protejeaza preparatul de aer si umezeala: cutie inchisa, hartie de copt intre straturi daca este fragil, si racire completa inainte de ambalare. Pentru deserturile prajite, accepta ca ziua prepararii ramane cea mai buna varianta.',
                ],
            ];
        case 'conserve-si-garnituri':
            return [
                [
                    'question' => 'De ce conteaza atat de mult borcanele curate sau sterilizate?',
                    'answer' => 'Pentru ca stabilitatea preparatului nu depinde doar de reteta, ci si de mediul in care il inchizi. Un borcan curat, uscat si manipulat corect reduce riscul de alterare si iti protejeaza munca.',
                ],
                [
                    'question' => 'Cat rezista preparatul?',
                    'answer' => 'Durata depinde de tipul retetei, de modul de depozitare si de felul in care a fost inchis. Pentru borcanele deschise, regula buna este frigider, ustensile curate si consum in cateva zile, nu in cateva saptamani.',
                ],
                [
                    'question' => 'Cum imi dau seama ca textura si gustul sunt in regula?',
                    'answer' => 'Uita-te la miros, culoare, lichidul din borcan si la felul in care se aseaza preparatul dupa racire. Daca ceva pare neobisnuit sau prea departe de ce ai obtinut de obicei, mai bine refaci decat sa fortezi folosirea lui.',
                ],
            ];
        default:
            return [];
    }
}

function kepoli_seed_render_faq_html(array $items, string $heading_id = ''): string
{
    if ($items === []) {
        return '';
    }

    $id_attr = $heading_id !== '' ? ' id="' . esc_attr($heading_id) . '"' : '';
    $html = '<section><h2' . $id_attr . '>Intrebari frecvente</h2>';
    foreach ($items as $item) {
        $html .= '<h3>' . esc_html($item['question']) . '</h3>';
        $html .= '<p>' . esc_html($item['answer']) . '</p>';
    }
    $html .= '</section>';

    return $html;
}

function kepoli_seed_article_takeaways_html(array $takeaways, string $heading = 'Pe scurt', string $heading_id = ''): string
{
    if ($takeaways === []) {
        return '';
    }

    $id_attr = $heading_id !== '' ? ' id="' . esc_attr($heading_id) . '"' : '';
    $html = '<section><h2' . $id_attr . '>' . esc_html($heading) . '</h2><ul>';
    foreach ($takeaways as $takeaway) {
        $html .= '<li>' . esc_html($takeaway) . '</li>';
    }
    $html .= '</ul></section>';

    return $html;
}

function kepoli_seed_recipe_content(array $post, array $post_ids, array $category_ids, array $posts): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $category_name = $category_id ? get_cat_name($category_id) : kepoli_seed_slug_to_title($post['category']);

    $prep_minutes = kepoli_seed_duration_minutes($post['prep']);
    $cook_minutes = kepoli_seed_duration_minutes($post['cook']);
    $total = $prep_minutes + $cook_minutes;
    $total_label = $total >= 60 ? floor($total / 60) . ' ora ' . ($total % 60 ? ($total % 60) . ' min' : '') : $total . ' min';

    $html = '';
    $html .= '<p>' . esc_html(kepoli_seed_post_intro($post)) . '</p>';
    $html .= '<p>Reteta face parte din categoria <a href="' . esc_url($category_link) . '">' . esc_html($category_name) . '</a> si este scrisa pentru gatit acasa, cu pasi clari si ingrediente usor de verificat.</p>';
    $html .= '[kepoli_ad slot="after_intro"]';
    $html .= kepoli_seed_article_takeaways_html($post['takeaways'] ?? [], 'Ce merita sa stii', 'ce-merita-sa-stii');
    $html .= '<section><h2 id="de-ce-merita">De ce merita reteta</h2>';
    foreach (kepoli_seed_recipe_value_paragraphs($post) as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '</section>';
    $html .= '<section class="kepoli-recipe-box">';
    $html .= '<h2 id="pe-scurt">Pe scurt</h2>';
    $html .= '<div class="kepoli-recipe-meta">';
    $html .= '<div><span>Pregatire</span><strong>' . esc_html($post['prep']) . '</strong></div>';
    $html .= '<div><span>Gatire</span><strong>' . esc_html($post['cook']) . '</strong></div>';
    $html .= '<div><span>Total</span><strong>' . esc_html(trim($total_label)) . '</strong></div>';
    $html .= '<div><span>Portii</span><strong>' . esc_html($post['servings']) . '</strong></div>';
    $html .= '</div>';
    $html .= '<h2 id="ingrediente">Ingrediente</h2><ul>';
    foreach ($post['ingredients'] as $ingredient) {
        $html .= '<li>' . esc_html($ingredient) . '</li>';
    }
    $html .= '</ul>';
    $html .= '<h2 id="mod-de-preparare">Mod de preparare</h2><ol>';
    foreach ($post['steps'] as $step) {
        $html .= '<li>' . esc_html($step) . '</li>';
    }
    $html .= '</ol>';
    $html .= '</section>';
    $html .= '[kepoli_ad slot="mid_content"]';
    $html .= '<section><h2 id="ingrediente-cheie">Ingrediente-cheie si adaptari</h2>';
    foreach (kepoli_seed_recipe_ingredient_focus_paragraphs($post) as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '</section>';
    $html .= '<h2 id="inainte-sa-incepi">Inainte sa incepi</h2>';
    foreach (kepoli_seed_recipe_intro_guidance($post) as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '<h2 id="sfaturi-pentru-reusita">Sfaturi pentru reusita</h2>';
    $html .= '<p>' . esc_html($post['notes']) . '</p>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_adjustment_text($post)) . '</p>';
    $html .= '<section><h2 id="greseli-frecvente">Greseli frecvente</h2><ul>';
    foreach (kepoli_seed_recipe_common_mistakes($post) as $item) {
        $html .= '<li>' . esc_html($item) . '</li>';
    }
    $html .= '</ul></section>';
    $html .= '<h2 id="cum-servesti">Cum servesti</h2>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_serving_text($post)) . '</p>';
    $html .= '<h2 id="cum-pastrezi">Cum pastrezi</h2>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_storage_text($post)) . '</p>';
    $html .= kepoli_seed_render_faq_html(kepoli_seed_recipe_faq($post), 'intrebari-frecvente');
    $html .= '<section class="related-posts"><h2 id="legaturi-utile">Legaturi utile</h2><p>Continua din aceeasi zona culinara cu retete si ghiduri care completeaza firesc preparatul de mai sus.</p><ul>';
    $html .= '<li><a href="' . esc_url($category_link) . '">Mai multe retete din ' . esc_html($category_name) . '</a> - archivea categoriei te ajuta sa compari preparate apropiate ca gust, tehnica sau moment de servire.</li>';
    foreach (array_merge($post['related'] ?? [], $post['related_articles'] ?? []) as $slug) {
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html(kepoli_seed_post_title($slug, $posts)) . '</a>';
        $related_excerpt = kepoli_seed_post_excerpt($slug, $posts);
        if ($related_excerpt !== '') {
            $html .= ' - ' . esc_html(wp_trim_words($related_excerpt, 18, '...'));
        }
        $html .= '</li>';
    }
    $html .= '</ul></section>';
    $html .= '<p><em>Nota: verifica mereu alergenii si adapteaza reteta la ingredientele tale.</em></p>';

    return $html;
}

function kepoli_seed_article_content(array $post, array $post_ids, array $category_ids, array $posts): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $html = '<p>' . esc_html(kepoli_seed_post_intro($post)) . '</p>';
    $html .= '<p>Acest ghid completeaza colectia de <a href="' . esc_url(home_url('/retete/')) . '">retete Kepoli</a> si arhiva de <a href="' . esc_url($category_link) . '">articole culinare</a>.</p>';
    $html .= kepoli_seed_article_takeaways_html($post['takeaways'] ?? []);
    $html .= '<section><h2>Ce gasesti in ghid</h2>';
    foreach (kepoli_seed_article_context_paragraphs($post) as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '</section>';

    $index = 0;
    foreach ($post['sections'] as $section) {
        $html .= '<h2>' . esc_html($section['heading']) . '</h2>';
        $html .= '<p>' . esc_html($section['body']) . '</p>';
        $index++;
        if ($index === 1) {
            $html .= '[kepoli_ad slot="mid_content"]';
        }
    }

    $html .= kepoli_seed_render_faq_html($post['faq'] ?? []);
    $html .= '<section><h2>Ce aplici mai intai</h2><p>' . esc_html(kepoli_seed_article_wrapup($post)) . '</p></section>';
    $html .= kepoli_seed_render_related_links(
        'Retete pe acelasi fir',
        'Porneste de la aceste retete daca vrei sa transformi ideile din articol in ceva concret de pus pe masa.',
        $post['related'] ?? [],
        $post_ids,
        $posts
    );

    return $html;
}

function kepoli_seed_reset_menu(string $name, string $location): int
{
    $menu = wp_get_nav_menu_object($name);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);

    foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
        wp_delete_post($item->ID, true);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $menu_id;
}

function kepoli_seed_menu_page(int $menu_id, string $title, int $page_id): void
{
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title' => $title,
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
    ]);
}

function kepoli_seed_menu_category(int $menu_id, string $title, int $category_id): void
{
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title' => $title,
        'menu-item-object' => 'category',
        'menu-item-object-id' => $category_id,
        'menu-item-type' => 'taxonomy',
        'menu-item-status' => 'publish',
    ]);
}

function kepoli_seed_activate_plugin(string $plugin): void
{
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
    if (!file_exists($plugin_path)) {
        return;
    }

    if (!function_exists('is_plugin_active') || !function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active($plugin)) {
        activate_plugin($plugin, '', false, true);
    }
}

function kepoli_seed_image_plan(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }

    $items = kepoli_seed_json($path);
    $plan = [];

    foreach ($items as $item) {
        if (!is_array($item) || empty($item['slug'])) {
            continue;
        }

        $plan[sanitize_title((string) $item['slug'])] = $item;
    }

    return $plan;
}

function kepoli_seed_image_path(array $image): string
{
    $base = '/content/images';
    $filename = sanitize_file_name((string) ($image['filename'] ?? ''));

    if ($filename !== '') {
        $path = $base . '/' . $filename;
        if (is_readable($path)) {
            return $path;
        }
    }

    $slug = sanitize_title((string) ($image['slug'] ?? ''));
    if ($slug === '') {
        return '';
    }

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $extension) {
        $path = $base . '/' . $slug . '.' . $extension;
        if (is_readable($path)) {
            return $path;
        }
    }

    return '';
}

function kepoli_seed_find_attachment(string $filename): int
{
    $ids = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => '_kepoli_seed_image_filename',
        'meta_value' => $filename,
    ]);

    if (!empty($ids)) {
        return (int) $ids[0];
    }

    $slug = sanitize_title(pathinfo($filename, PATHINFO_FILENAME));
    $attachment = $slug !== '' ? get_page_by_path($slug, OBJECT, 'attachment') : null;

    return $attachment ? (int) $attachment->ID : 0;
}

function kepoli_seed_apply_attachment_meta(int $attachment_id, array $image): void
{
    $alt = sanitize_text_field((string) ($image['alt'] ?? ''));
    $title = sanitize_text_field((string) ($image['title'] ?? ''));
    $caption = sanitize_text_field((string) ($image['caption'] ?? ''));
    $description = sanitize_textarea_field((string) ($image['description'] ?? ''));

    if ($alt !== '') {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', substr($alt, 0, 160));
    }

    $attachment_update = ['ID' => $attachment_id];
    if ($title !== '') {
        $attachment_update['post_title'] = substr($title, 0, 90);
    }
    if ($caption !== '') {
        $attachment_update['post_excerpt'] = substr($caption, 0, 180);
    }
    if ($description !== '') {
        $attachment_update['post_content'] = substr($description, 0, 320);
    }

    if (count($attachment_update) > 1) {
        wp_update_post(wp_slash($attachment_update), true);
    }
}

function kepoli_seed_store_image_plan_meta(int $post_id, array $image): void
{
    update_post_meta($post_id, '_kepoli_image_plan_filename', sanitize_file_name((string) ($image['filename'] ?? '')));
    update_post_meta($post_id, '_kepoli_image_plan_alt', sanitize_text_field((string) ($image['alt'] ?? '')));
    update_post_meta($post_id, '_kepoli_image_plan_title', sanitize_text_field((string) ($image['title'] ?? '')));
    update_post_meta($post_id, '_kepoli_image_plan_caption', sanitize_text_field((string) ($image['caption'] ?? '')));
    update_post_meta($post_id, '_kepoli_image_plan_description', sanitize_textarea_field((string) ($image['description'] ?? '')));
    update_post_meta($post_id, '_kepoli_image_plan_prompt', sanitize_textarea_field((string) ($image['prompt'] ?? '')));
}

function kepoli_seed_import_featured_image(int $post_id, array $image): void
{
    kepoli_seed_store_image_plan_meta($post_id, $image);

    $filename = sanitize_file_name((string) ($image['filename'] ?? ''));
    if ($filename === '') {
        return;
    }

    $source = kepoli_seed_image_path($image);
    if ($source === '') {
        return;
    }

    $source_hash = hash_file('sha256', $source) ?: '';
    $attachment_id = kepoli_seed_find_attachment($filename);
    if ($attachment_id && $source_hash !== '' && (string) get_post_meta($attachment_id, '_kepoli_seed_image_hash', true) !== $source_hash) {
        wp_delete_attachment($attachment_id, true);
        $attachment_id = 0;
    }

    if (!$attachment_id) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $bits = file_get_contents($source);
        if ($bits === false) {
            throw new RuntimeException("Cannot read image {$source}");
        }

        $upload = wp_upload_bits($filename, null, $bits);
        if (!empty($upload['error'])) {
            throw new RuntimeException((string) $upload['error']);
        }

        $filetype = wp_check_filetype($upload['file'], null);
        $attachment_id = wp_insert_attachment(wp_slash([
            'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
            'post_title' => sanitize_text_field((string) ($image['title'] ?? pathinfo($filename, PATHINFO_FILENAME))),
            'post_excerpt' => sanitize_text_field((string) ($image['caption'] ?? '')),
            'post_content' => sanitize_textarea_field((string) ($image['description'] ?? '')),
            'post_status' => 'inherit',
            'post_parent' => $post_id,
        ]), $upload['file'], $post_id, true);

        if (is_wp_error($attachment_id)) {
            throw new RuntimeException($attachment_id->get_error_message());
        }

        $metadata = wp_generate_attachment_metadata((int) $attachment_id, $upload['file']);
        if (!is_wp_error($metadata) && !empty($metadata)) {
            wp_update_attachment_metadata((int) $attachment_id, $metadata);
        }

        update_post_meta((int) $attachment_id, '_kepoli_seed_image_filename', $filename);
        update_post_meta((int) $attachment_id, '_kepoli_seed_image_slug', sanitize_title((string) ($image['slug'] ?? '')));
    }

    wp_update_post(wp_slash([
        'ID' => (int) $attachment_id,
        'post_parent' => $post_id,
    ]), true);

    kepoli_seed_apply_attachment_meta((int) $attachment_id, $image);
    update_post_meta((int) $attachment_id, '_kepoli_seed_image_filename', $filename);
    update_post_meta((int) $attachment_id, '_kepoli_seed_image_slug', sanitize_title((string) ($image['slug'] ?? '')));
    if ($source_hash !== '') {
        update_post_meta((int) $attachment_id, '_kepoli_seed_image_hash', $source_hash);
    }
    if (!set_post_thumbnail($post_id, (int) $attachment_id)) {
        update_post_meta($post_id, '_thumbnail_id', (int) $attachment_id);
    }
}

function kepoli_seed_delete_placeholder_posts(array $expected_slugs): void
{
    $expected = array_flip(array_map('sanitize_title', $expected_slugs));
    $placeholder_markers = [
        'Scrie aici de ce merita pregatita reteta',
        'Ingredient 1',
        'Descrie primul pas clar',
        'Continua cu pasii in ordinea fireasca',
        'Incheie cu momentul in care preparatul este gata',
        'Adauga ajustari, greseli de evitat',
        'Raspunde practic, cu intervale realiste',
    ];

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => ['publish', 'draft', 'pending', 'future'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    foreach ($query->posts as $post_id) {
        $slug = (string) get_post_field('post_name', $post_id);
        if ($slug !== '' && isset($expected[$slug])) {
            continue;
        }

        $content = (string) get_post_field('post_content', $post_id);
        foreach ($placeholder_markers as $marker) {
            if (str_contains($content, $marker)) {
                wp_delete_post((int) $post_id, true);
                break;
            }
        }
    }
}

if (wp_get_theme()->get_stylesheet() !== 'kepoli' && wp_get_theme('kepoli')->exists()) {
    switch_theme('kepoli');
}

kepoli_seed_activate_plugin('kepoli-author-tools/kepoli-author-tools.php');

update_option('blogname', 'Kepoli');
update_option('blogdescription', 'Retete romanesti si articole de bucatarie pentru acasa');
update_option('admin_email', kepoli_seed_env('SITE_EMAIL', 'contact@kepoli.com'));
update_option('blog_public', '1');
update_option('timezone_string', 'Europe/Bucharest');
update_option('date_format', 'j F Y');
update_option('time_format', 'H:i');
update_option('posts_per_page', 9);
update_option('default_role', 'subscriber');
update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');
update_option('require_name_email', '1');
update_option('close_comments_for_old_posts', '1');
update_option('close_comments_days_old', '14');

global $wp_rewrite;
if ($wp_rewrite instanceof WP_Rewrite) {
    $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
}

$author_id = kepoli_seed_ensure_author();
$categories = kepoli_seed_json('/content/categories.json');
$pages = kepoli_seed_json('/content/pages.json');
$posts = kepoli_seed_json('/content/posts.json');
$image_plan = kepoli_seed_image_plan('/content/image-plan.json');
kepoli_seed_delete_placeholder_posts(array_column($posts, 'slug'));

$category_ids = [];
foreach ($categories as $category) {
    $category_ids[$category['slug']] = kepoli_seed_ensure_category($category);
}

$page_ids = [];
foreach ($pages as $page) {
    $page_ids[$page['slug']] = kepoli_seed_upsert_page($page, $author_id);
}

update_option('show_on_front', 'page');
update_option('page_on_front', $page_ids['acasa'] ?? 0);

$sample = get_page_by_path('hello-world', OBJECT, 'post');
if ($sample) {
    wp_delete_post($sample->ID, true);
}
$sample_page = get_page_by_path('sample-page', OBJECT, 'page');
if ($sample_page) {
    wp_delete_post($sample_page->ID, true);
}

$post_ids = [];
foreach ($posts as $index => $post) {
    $existing = get_page_by_path($post['slug'], OBJECT, 'post');
    $date = gmdate('Y-m-d H:i:s', strtotime('2026-03-01 +' . $index . ' days'));
    $postarr = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_author' => $author_id,
        'post_name' => $post['slug'],
        'post_title' => $post['title'],
        'post_excerpt' => $post['excerpt'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_content' => '<p>' . esc_html(kepoli_seed_post_intro($post)) . '</p>',
        'post_date' => $date,
        'post_date_gmt' => get_gmt_from_date($date),
    ];
    if ($existing) {
        $postarr['ID'] = $existing->ID;
        unset($postarr['post_date'], $postarr['post_date_gmt']);
    }

    $post_id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($post_id)) {
        throw new RuntimeException($post_id->get_error_message());
    }

    $post_ids[$post['slug']] = (int) $post_id;
    if (isset($category_ids[$post['category']])) {
        wp_set_post_terms($post_id, [$category_ids[$post['category']]], 'category', false);
    }
    wp_set_post_terms($post_id, $post['tags'] ?? [], 'post_tag', false);
    update_post_meta($post_id, '_kepoli_post_kind', $post['kind']);
    update_post_meta($post_id, '_kepoli_related_recipe_slugs', $post['related'] ?? []);
    update_post_meta($post_id, '_kepoli_related_article_slugs', $post['related_articles'] ?? []);
    update_post_meta($post_id, '_kepoli_related_slugs', array_values(array_unique(array_merge($post['related'] ?? [], $post['related_articles'] ?? []))));
    update_post_meta($post_id, '_kepoli_meta_description', $post['meta_description'] ?? $post['excerpt']);
    update_post_meta($post_id, '_kepoli_seo_title', $post['seo_title'] ?? $post['title']);

    if (isset($image_plan[$post['slug']])) {
        kepoli_seed_import_featured_image((int) $post_id, $image_plan[$post['slug']]);
    }
}

foreach ($posts as $post) {
    $post_id = $post_ids[$post['slug']];
    if ($post['kind'] === 'recipe') {
        $content = kepoli_seed_recipe_content($post, $post_ids, $category_ids, $posts);
        $prep_minutes = kepoli_seed_duration_minutes($post['prep']);
        $cook_minutes = kepoli_seed_duration_minutes($post['cook']);
        update_post_meta($post_id, '_kepoli_recipe_json', wp_json_encode([
            'category' => get_cat_name($category_ids[$post['category']] ?? 0),
            'servings' => $post['servings'],
            'prep_iso' => kepoli_seed_iso_duration($post['prep']),
            'cook_iso' => kepoli_seed_iso_duration($post['cook']),
            'total_iso' => 'PT' . ($prep_minutes + $cook_minutes) . 'M',
            'ingredients' => $post['ingredients'],
            'steps' => $post['steps'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        $content = kepoli_seed_article_content($post, $post_ids, $category_ids, $posts);
        update_post_meta($post_id, '_kepoli_article_snapshot', wp_json_encode(
            kepoli_seed_article_snapshot_meta($post),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    wp_update_post(wp_slash([
        'ID' => $post_id,
        'post_content' => $content,
    ]), true);
}

$primary_menu = kepoli_seed_reset_menu('Primary', 'primary');
kepoli_seed_menu_page($primary_menu, 'Acasa', $page_ids['acasa']);
kepoli_seed_menu_page($primary_menu, 'Retete', $page_ids['retete']);
kepoli_seed_menu_category($primary_menu, 'Ciorbe si supe', $category_ids['ciorbe-si-supe']);
kepoli_seed_menu_category($primary_menu, 'Feluri principale', $category_ids['feluri-principale']);
kepoli_seed_menu_category($primary_menu, 'Deserturi', $category_ids['patiserie-si-deserturi']);
kepoli_seed_menu_page($primary_menu, 'Articole', $page_ids['articole']);
kepoli_seed_menu_page($primary_menu, 'Despre', $page_ids['despre-kepoli']);

$footer_menu = kepoli_seed_reset_menu('Footer', 'footer');
foreach (['despre-kepoli', 'despre-autor', 'contact', 'politica-de-confidentialitate', 'politica-de-cookies', 'publicitate-si-consimtamant', 'politica-editoriala', 'termeni-si-conditii', 'disclaimer-culinar'] as $slug) {
    kepoli_seed_menu_page($footer_menu, $pages[array_search($slug, array_column($pages, 'slug'), true)]['title'], $page_ids[$slug]);
}

update_option('default_category', $category_ids['ciorbe-si-supe'] ?? 1);
update_option('posts_per_page', 9);
update_option('kepoli_seed_version', kepoli_seed_target_version());
flush_rewrite_rules(false);

echo "Seeded " . count($posts) . " posts and " . count($pages) . " pages.\n";

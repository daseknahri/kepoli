<?php
/**
 * Idempotent content and site bootstrap for Kepoli.
 */

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

function kepoli_seed_article_takeaways_html(array $takeaways): string
{
    if ($takeaways === []) {
        return '';
    }

    $html = '<section><h2>Pe scurt</h2><ul>';
    foreach ($takeaways as $takeaway) {
        $html .= '<li>' . esc_html($takeaway) . '</li>';
    }
    $html .= '</ul></section>';

    return $html;
}

function kepoli_seed_recipe_content(array $post, array $post_ids, array $category_ids): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $category_name = $category_id ? get_cat_name($category_id) : kepoli_seed_slug_to_title($post['category']);

    $prep_minutes = kepoli_seed_duration_minutes($post['prep']);
    $cook_minutes = kepoli_seed_duration_minutes($post['cook']);
    $total = $prep_minutes + $cook_minutes;
    $total_label = $total >= 60 ? floor($total / 60) . ' ora ' . ($total % 60 ? ($total % 60) . ' min' : '') : $total . ' min';

    $html = '';
    $html .= '<p>' . esc_html($post['excerpt']) . '</p>';
    $html .= '<p>Reteta face parte din categoria <a href="' . esc_url($category_link) . '">' . esc_html($category_name) . '</a> si este scrisa pentru gatit acasa, cu pasi clari si ingrediente usor de verificat.</p>';
    $html .= '[kepoli_ad slot="after_intro"]';
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
    $html .= '<h2 id="inainte-sa-incepi">Inainte sa incepi</h2>';
    foreach (kepoli_seed_recipe_intro_guidance($post) as $paragraph) {
        $html .= '<p>' . esc_html($paragraph) . '</p>';
    }
    $html .= '<h2 id="sfaturi-pentru-reusita">Sfaturi pentru reusita</h2>';
    $html .= '<p>' . esc_html($post['notes']) . '</p>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_adjustment_text($post)) . '</p>';
    $html .= '<h2 id="cum-servesti">Cum servesti</h2>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_serving_text($post)) . '</p>';
    $html .= '<h2 id="cum-pastrezi">Cum pastrezi</h2>';
    $html .= '<p>' . esc_html(kepoli_seed_recipe_storage_text($post)) . '</p>';
    $html .= kepoli_seed_render_faq_html(kepoli_seed_recipe_faq($post), 'intrebari-frecvente');
    $html .= '<section class="related-posts"><h2 id="legaturi-utile">Legaturi utile</h2><ul>';
    $html .= '<li><a href="' . esc_url($category_link) . '">Mai multe retete din ' . esc_html($category_name) . '</a></li>';
    foreach (array_merge($post['related'] ?? [], $post['related_articles'] ?? []) as $slug) {
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html(kepoli_seed_slug_to_title($slug)) . '</a></li>';
    }
    $html .= '</ul></section>';
    $html .= '<p><em>Nota: verifica mereu alergenii si adapteaza reteta la ingredientele tale.</em></p>';

    return $html;
}

function kepoli_seed_article_content(array $post, array $post_ids, array $category_ids): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $html = '<p>' . esc_html($post['excerpt']) . '</p>';
    $html .= '<p>Acest ghid completeaza colectia de <a href="' . esc_url(home_url('/retete/')) . '">retete Kepoli</a> si arhiva de <a href="' . esc_url($category_link) . '">articole culinare</a>.</p>';
    $html .= kepoli_seed_article_takeaways_html($post['takeaways'] ?? []);
    $html .= '<p>Citeste-l cap-coada daca planifici o masa, o sesiune de gatit sau o lista de cumparaturi. Daca esti deja in bucatarie, foloseste subtitlurile pentru partea care te intereseaza acum.</p>';

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
    $html .= '<section class="related-posts"><h2>Retete pe acelasi fir</h2><ul>';
    foreach ($post['related'] ?? [] as $slug) {
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html(kepoli_seed_slug_to_title($slug)) . '</a></li>';
    }
    $html .= '</ul></section>';

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
        'post_content' => '<p>' . esc_html($post['excerpt']) . '</p>',
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
}

foreach ($posts as $post) {
    $post_id = $post_ids[$post['slug']];
    if ($post['kind'] === 'recipe') {
        $content = kepoli_seed_recipe_content($post, $post_ids, $category_ids);
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
        $content = kepoli_seed_article_content($post, $post_ids, $category_ids);
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
foreach (['despre-kepoli', 'despre-autor', 'contact', 'politica-de-confidentialitate', 'politica-de-cookies', 'publicitate-si-consimtamant', 'termeni-si-conditii', 'disclaimer-culinar'] as $slug) {
    kepoli_seed_menu_page($footer_menu, $pages[array_search($slug, array_column($pages, 'slug'), true)]['title'], $page_ids[$slug]);
}

update_option('default_category', $category_ids['ciorbe-si-supe'] ?? 1);
update_option('posts_per_page', 9);
update_option('kepoli_seed_version', '2026-04-21-content-depth');
flush_rewrite_rules(false);

echo "Seeded " . count($posts) . " posts and " . count($pages) . " pages.\n";

<?php
session_start();
require_once __DIR__ . '/conexao.php';

$mensagem = '';
$tipo_mensagem = '';

// =========================================================================
// CONFIGURAÇÕES DO SUAP E PERMISSÕES
// =========================================================================

// Lembre-se de ajustar com a URL do seu campus
$url_suap_base = 'https://suap.iff.edu.br'; 
$prefixos_autorizados = ['20241917', '20241978'];

// Matrículas específicas avulsas (adicione outras aqui no futuro)
$matriculas_especificas = [
    '202319170313',
    '202319170615',
];

/**
 * Autentica o usuário na API do SUAP
 */
function validarLoginSuap($matricula, $senha, $url_base) {
    $urlToken = rtrim($url_base, '/') . '/api/v2/autenticacao/token/';

    $ch = curl_init($urlToken);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $matricula,
        'password' => $senha
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200);
}

// =========================================================================
// PROCESSAMENTO DE LOGIN E LOGOUT
// =========================================================================

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: plantas.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_suap'])) {
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (validarLoginSuap($matricula, $senha, $url_suap_base)) {
        $_SESSION['usuario_matricula'] = $matricula;
        $mensagem = "Login realizado com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Credenciais inválidas no SUAP.";
        $tipo_mensagem = "erro";
    }
}

// Verifica status de login e autorização
$is_logged_in = isset($_SESSION['usuario_matricula']);
$is_authorized = false;

if ($is_logged_in) {
    $mat = (string)$_SESSION['usuario_matricula'];

    // 1. Valida se a matrícula COMEÇA com algum dos prefixos
    foreach ($prefixos_autorizados as $prefixo) {
        if (strncmp($mat, $prefixo, strlen($prefixo)) === 0) {
            $is_authorized = true;
            break;
        }
    }

    // 2. Valida se a matrícula está na lista específica
    if (!$is_authorized && in_array($mat, $matriculas_especificas)) {
        $is_authorized = true;
    }
}

// =========================================================================
// INTEGRAÇÃO COM GEMINI
// =========================================================================

function enviarMensagemGeminiConversaContinua($pdo, $novoPrompt) {
    $apiKey = getenv('GEMINI_API_KEY');
    $apiKey = $apiKey !== false ? trim($apiKey) : '';
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key={$apiKey}";
    
    $stmtHist = $pdo->query("SELECT role, texto FROM historico_conversa ORDER BY id ASC");
    $historicoBanco = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

    $contents = [];

    if (empty($historicoBanco)) {
        $contents[] = [
            "role" => "user",
            "parts" => [["text" => "Você é um assistente agrônomo especialista em agroecologia para uma horta escolar. A partir de agora, catalogaremos várias plantas. Responda SEMPRE em formato JSON válido contendo as exatas chaves: 'luz', 'rega', 'consorcio' e 'manejo'."]]
        ];
        $contents[] = [
            "role" => "model",
            "parts" => [["text" => '{"luz": "Entendido", "rega": "Pronto", "consorcio": "Para", "manejo": "Analisar"}']]
        ];
    }

    foreach ($historicoBanco as $msg) {
        $contents[] = [
            "role" => $msg['role'],
            "parts" => [["text" => $msg['texto']]]
        ];
    }

    $contents[] = [
        "role" => "user",
        "parts" => [["text" => $novoPrompt]]
    ];

    $payload = [
        "contents" => $contents,
        "generationConfig" => [
            "responseMimeType" => "application/json"
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        echo "<div style='background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 8px; margin: 20px; font-family: sans-serif;'>";
        echo "<h3>Erro no cURL:</h3><p>{$error}</p></div>";
        exit;
    }

    curl_close($ch);
    $resultado = json_decode($response, true);

    if ($httpCode !== 200 || isset($resultado['error'])) {
        echo "<div style='background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 8px; margin: 20px; font-family: sans-serif;'>";
        echo "<h3>Erro Retornado pela API do Gemini:</h3>";
        echo "<p><strong>Código HTTP:</strong> {$httpCode}</p>";
        echo "<pre>"; print_r($resultado['error'] ?? $resultado); echo "</pre>";
        echo "</div>";
        exit;
    }

    $respostaIA = $resultado['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($respostaIA) {
        $stmtInsert = $pdo->prepare("INSERT INTO historico_conversa (role, texto) VALUES ('user', ?), ('model', ?)");
        $stmtInsert->execute([$novoPrompt, $respostaIA]);
        return json_decode($respostaIA, true);
    }

    return [
        'luz' => 'Sol pleno ou meia-sombra.',
        'rega' => 'Manter solo levemente úmido.',
        'consorcio' => 'Rotação de culturas recomendada.',
        'manejo' => 'Adubação orgânica.'
    ];
}

// =========================================================================
// PROCESSAMENTO DO CADASTRO DA PLANTA (Protegido)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_planta'])) {
    if (!$is_authorized) {
        $mensagem = "Erro: Você não tem permissão para realizar cadastros.";
        $tipo_mensagem = "erro";
    } else {
        $nome_planta  = trim($_POST['nome_planta'] ?? '');
        $especie_tipo = trim($_POST['especie_tipo'] ?? '');
        $data_plantio = !empty($_POST['data_plantio']) ? $_POST['data_plantio'] : null;
        $observacoes  = trim($_POST['observacoes'] ?? '');
        $imagem_path  = null;

        if (isset($_FILES['foto_planta']) && $_FILES['foto_planta']['error'] === UPLOAD_ERR_OK) {
            $extensao = strtolower(pathinfo($_FILES['foto_planta']['name'], PATHINFO_EXTENSION));
            if (in_array($extensao, ['jpg', 'jpeg', 'png', 'webp'])) {
                $novo_nome = uniqid('planta_', true) . '.' . $extensao;
                $diretorio = __DIR__ . '/uploads/';
                if (!is_dir($diretorio)) mkdir($diretorio, 0755, true);
                if (move_uploaded_file($_FILES['foto_planta']['tmp_name'], $diretorio . $novo_nome)) {
                    $imagem_path = 'uploads/' . $novo_nome;
                }
            }
        }

        if (!empty($nome_planta) && !empty($especie_tipo)) {
            try {
                $promptPlanta = "Registre a planta '{$nome_planta}' da categoria '{$especie_tipo}'. Forneça os dados de luz, rega, consorcio e manejo considerando o histórico ecológico.";
                
                $dadosManejo = enviarMensagemGeminiConversaContinua($pdo, $promptPlanta);
                $manejoJson = json_encode($dadosManejo, JSON_UNESCAPED_UNICODE);

                $stmt = $pdo->prepare("INSERT INTO plantas (nome_planta, especie_tipo, data_plantio, observacoes, imagem, manejo_json) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome_planta, $especie_tipo, $data_plantio, $observacoes, $imagem_path, $manejoJson]);
                
                $mensagem = "Espécie registrada e analisada na conversa contínua do Gemini!";
                $tipo_mensagem = "sucesso";
            } catch (PDOException $e) {
                $mensagem = "Erro ao registrar: " . $e->getMessage();
                $tipo_mensagem = "erro";
            }
        } else {
            $mensagem = "Preencha os campos obrigatórios (*).";
            $tipo_mensagem = "erro";
        }
    }
}

// Busca as plantas apenas se o usuário estiver logado
$plantas = [];
if ($is_logged_in) {
    try {
        $stmt = $pdo->query("SELECT * FROM plantas ORDER BY data_cadastro DESC");
        $plantas = $stmt->fetchAll();
    } catch (PDOException $e) {
        $plantas = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Agroecológico | Raízes da Escola</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/logo-raizes.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
    <style>
        .alerta {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 24px;
            font-size: 0.95rem;
        }
        .alerta.sucesso {
            background-color: #e6f4ea;
            color: #137333;
            border: 1px solid #ceebd6;
        }
        .alerta.erro {
            background-color: #fce8e6;
            color: #c5221f;
            border: 1px solid #fad2cf;
        }
        .form-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-bottom: 32px;
        }
        .custom-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 16px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #2d3748;
        }
        .custom-form input[type="text"],
        .custom-form input[type="password"],
        .custom-form input[type="date"],
        .custom-form select,
        .custom-form textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'DM Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }
        .custom-form input:focus,
        .custom-form select:focus,
        .custom-form textarea:focus {
            outline: none;
            border-color: #1b4332;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(27, 67, 50, 0.12);
        }
        .custom-form input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 0.875rem;
            box-sizing: border-box;
            cursor: pointer;
        }
        .primary-button {
            width: 100%;
            padding: 14px 20px;
            background-color: #1b4332;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }
        .primary-button:hover { background-color: #2d6a4f; }
        .primary-button:active { transform: scale(0.98); }
        .logout-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #991b1b;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            background: #fee2e2;
            padding: 6px 12px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }
        .logout-link:hover { background: #fca5a5; }
    </style>
</head>
<body>

    <header class="site-header">
        <a class="brand" href="../index.html#inicio" aria-label="Raízes da Escola - início"><img class="brand-mark"
            src="../assets/images/logo-raizes.svg" alt=""><span>Raízes <em>da escola</em></span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav"
            aria-label="Abrir menu"><span></span><span></span><span></span></button>
        <nav id="main-nav" class="main-nav" aria-label="Navegação principal">
            <a class="nav-link" href="../index.html#inicio">Início</a>
            <a class="nav-link" href="../index.html#projeto">O projeto</a>
            <a class="nav-link" href="../index.html#agroecologia">Agroecologia</a>
            <a class="nav-link is-active" href="plantas.php">Catálogo <span class="nav-dot" aria-hidden="true"></span></a>
        </nav>
        <a class="instagram-link" href="https://www.instagram.com/espaco.agroecologico.iff?stkn=bndmaWpqZjhsc2Vz" target="_blank" rel="noreferrer"
            aria-label="Instagram do projeto" title="Instagram do projeto">
            <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                <rect x="3" y="3" width="18" height="18" rx="5"></rect>
                <circle cx="12" cy="12" r="4"></circle>
                <circle cx="17.5" cy="6.5" r="1"></circle>
            </svg>
        </a>
        <a class="header-action" href="../index.html#projeto">Conheça o Projeto <span aria-hidden="true">&#8599;</span></a>
    </header>

    <main>
        <section class="page-section catalog-hero">
            <p class="eyebrow"><span></span> Acervo Vivo & IA Integrada</p>
            <h1>Catálogo<br><i>agroecológico.</i></h1>
            <p class="hero-intro">
                Mapeamento botânico assistido por Inteligência Artificial. Faça login para visualizar e registrar espécies.
            </p>
        </section>

        <section class="page-section catalog-layout">
            
            <?php if (!empty($mensagem)): ?>
                <div class="alerta <?= $tipo_mensagem ?>">
                    <span><?= $tipo_mensagem === 'sucesso' ? '✓' : '⚠' ?></span>
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <?php if (!$is_logged_in): ?>
                <!-- TELA DE LOGIN SUAP PARA DESLOGADOS -->
                <aside class="form-container" style="max-width: 500px; margin: 0 auto;">
                    <div class="section-heading">
                        <p class="eyebrow"><span></span> Acesso Restrito</p>
                        <h2>Identifique-se<br><i>no SUAP.</i></h2>
                        <p style="margin-top: 10px; color: #666;">Você precisa estar autenticado para acessar o acervo da horta.</p>
                    </div>

                    <form action="plantas.php" method="POST" class="custom-form">
                        <div class="form-group">
                            <label for="matricula">Matrícula</label>
                            <input type="text" id="matricula" name="matricula" placeholder="Digite sua matrícula" required>
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha do SUAP</label>
                            <input type="password" id="senha" name="senha" placeholder="Sua senha institucional" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="login_suap" class="primary-button">
                                Entrar no Sistema &#8594;
                            </button>
                        </div>
                    </form>
                </aside>

            <?php else: ?>
                <!-- CONTEÚDO EXIBIDO APENAS APÓS LOGIN -->

                <?php if (!$is_authorized): ?>
                    <!-- PAINEL VISITANTE -->
                    <aside class="form-container">
                        <div class="section-heading">
                            <p class="eyebrow"><span></span> Matrícula: <?= htmlspecialchars($_SESSION['usuario_matricula']) ?></p>
                            <h2>Acesso<br><i>Visitante.</i></h2>
                            <p style="margin-bottom: 20px;">Sua conta possui permissão apenas para leitura do acervo.</p>
                            <a href="?logout=1" class="logout-link">Sair da conta</a>
                        </div>
                    </aside>
                <?php else: ?>
                    <!-- PAINEL ADMIN (AUTORIZADO A CADASTRAR) -->
                    <aside class="form-container">
                        <div class="section-heading">
                            <p class="eyebrow"><span></span> Administrativo</p>
                            <h2>Registrar<br><i>espécie.</i></h2>
                        </div>
                        
                        <a href="?logout=1" class="logout-link">Encerrar sessão de <?= htmlspecialchars($_SESSION['usuario_matricula']) ?></a>

                        <form action="plantas.php" method="POST" enctype="multipart/form-data" class="custom-form">
                            <div class="form-group">
                                <label for="nome_planta">Nome da Planta / Erva *</label>
                                <input type="text" id="nome_planta" name="nome_planta" placeholder="Ex: Taioba, Manjericão" required>
                            </div>

                            <div class="form-group">
                                <label for="especie_tipo">Categoria *</label>
                                <select id="especie_tipo" name="especie_tipo" required>
                                    <option value="">Selecione uma categoria</option>
                                    <option value="Hortaliça">Hortaliça</option>
                                    <option value="Medicinal">Medicinal / Aromática</option>
                                    <option value="PANC">PANC (Alimentícia Não Convencional)</option>
                                    <option value="Adubação Verde">Adubação Verde</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="data_plantio">Data do Plantio</label>
                                <input type="date" id="data_plantio" name="data_plantio">
                            </div>

                            <div class="form-group">
                                <label for="foto_planta">Fotografia da Espécie</label>
                                <input type="file" id="foto_planta" name="foto_planta" accept="image/*">
                            </div>

                            <div class="form-group">
                                <label for="observacoes">Observações de Campo</label>
                                <textarea id="observacoes" name="observacoes" rows="3" placeholder="Ex: Canteiro 01, solo adubado..."></textarea>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="cadastrar_planta" class="primary-button">
                                    Salvar e Analisar na IA &#8594;
                                </button>
                            </div>
                        </form>
                    </aside>
                <?php endif; ?>

                <!-- GRID DE PLANTAS DO CANTEIRO (Exibido para Visitantes e Admin) -->
                <div class="catalog-content">
                    <div class="section-heading">
                        <p class="eyebrow"><span></span> Mapeamento</p>
                        <h2>Canteiro ativo<br><i>(<?= count($plantas) ?> espécies).</i></h2>
                    </div>

                    <?php if (empty($plantas)): ?>
                        <p>Nenhuma planta cadastrada no momento.</p>
                    <?php else: ?>
                        <div class="plants-grid">
                            <?php foreach ($plantas as $planta): ?>
                                <?php 
                                    $dicas = !empty($planta['manejo_json']) ? json_decode($planta['manejo_json'], true) : [];
                                    $diasPlantado = null;
                                    if (!empty($planta['data_plantio'])) {
                                        $diasPlantado = (new DateTime())->diff(new DateTime($planta['data_plantio']))->days;
                                    }
                                ?>
                                <article class="plant-card">
                                    <div class="card-media">
                                        <?php if (!empty($planta['imagem'])): ?>
                                            <img src="<?= htmlspecialchars($planta['imagem']) ?>" alt="<?= htmlspecialchars($planta['nome_planta']) ?>">
                                        <?php else: ?>
                                            <div class="media-placeholder"><span>Sem foto registrada</span></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-body">
                                        <span class="tag-category"><?= htmlspecialchars($planta['especie_tipo']) ?></span>
                                        <h3><?= htmlspecialchars($planta['nome_planta']) ?></h3>
                                        
                                        <p class="meta-date">
                                            <strong>Plantio:</strong> 
                                            <?= $planta['data_plantio'] ? date('d/m/Y', strtotime($planta['data_plantio'])) : 'Não registrada' ?>
                                            <?php if ($diasPlantado !== null): ?>
                                                • <span class="badge-days"><?= $diasPlantado ?> dias</span>
                                            <?php endif; ?>
                                        </p>

                                        <?php if (!empty($planta['observacoes'])): ?>
                                            <p class="card-obs"><?= nl2br(htmlspecialchars($planta['observacoes'])) ?></p>
                                        <?php endif; ?>

                                        <div class="agro-dicas">
                                            <p class="dica-title">Manejo via Gemini (Conversa Ativa)</p>
                                            <ul>
                                                <li><strong>Luz:</strong> <?= htmlspecialchars($dicas['luz'] ?? 'N/D') ?></li>
                                                <li><strong>Rega:</strong> <?= htmlspecialchars($dicas['rega'] ?? 'N/D') ?></li>
                                                <li><strong>Consórcio:</strong> <?= htmlspecialchars($dicas['consorcio'] ?? 'N/D') ?></li>
                                                <li><strong>Manejo Orgânico:</strong> <?= htmlspecialchars($dicas['manejo'] ?? 'N/D') ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-brand">Raízes <em>da escola</em></div>
        <p>Laboratório de Agroecologia e Sustentabilidade.</p>
        <span>&copy; <?= date('Y') ?></span>
    </footer>
    <script type="module" src="../js/script.js"></script>
</body>
</html>

/** URL da foto padrão quando não há login ou após sair da conta */
const FOTO_PERFIL_PADRAO =
  "https://cdn-icons-png.flaticon.com/512/149/149071.png"

/**
 * URL final da API (absoluta com /, ou resolvida a partir da página).
 */
function resolveApiUrl() {
  if (typeof window === "undefined") {
    return "api.php"
  }
  if (window.__API_URL__) {
    const u = String(window.__API_URL__).trim()
    if (/^https?:\/\//i.test(u)) {
      return u
    }
    if (u.startsWith("/")) {
      return window.location.origin + u
    }
    return new URL(u, window.location.href).href
  }
  return new URL("api.php", window.location.href).href
}

const API_URL = resolveApiUrl()

/**
 * ID do cliente OAuth (Web) — também definido em config.php (GOOGLE_CLIENT_ID) para validar o token.
 */
const GOOGLE_CLIENT_ID =
  typeof window !== "undefined" && window.__GOOGLE_CLIENT_ID__
    ? window.__GOOGLE_CLIENT_ID__
    : ""

let usuarioLogado = null
let usuarioPerfil = null
let appMeta = null
let myRating = null
let ratingSelecionado = 0

let favoritos = []
let adotados = []
let ongs = []
let denunciasLista = []

/** IDs dos animais pré-definidos no site (não gravados em animaisExtras) */
const IDS_ANIMAIS_PADRAO = new Set([
  1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
])

/** Lista base exibida no site; animais extras vêm do servidor (PHP). */
const animaisBase = [
  {
    id: 1,
    nome: "Luna",
    tipo: "Gato",
    idade: "2 anos",
    sexo: "Fêmea",
    deficiencia: "Nenhuma",
    vacinas: "V4",
    alergia: "Frango",
    foto: "https://cdn2.thecatapi.com/images/MTY3ODIyMQ.jpg",
    whatsapp: "5511999999999",
    ong: "",
  },
  {
    id: 2,
    nome: "Thor",
    tipo: "Cachorro",
    idade: "3 anos",
    sexo: "Macho",
    deficiencia: "Surdez",
    vacinas: "V10",
    alergia: "Não possui",
    foto: "https://blog-static.petlove.com.br/wp-content/uploads/2020/01/cao_jogan.jpeg",
    whatsapp: "5511888888888",
    ong: "",
  },
  {
    id: 3,
    nome: "Mel",
    tipo: "Cachorro",
    idade: "1 ano",
    sexo: "Fêmea",
    deficiencia: "Perdeu uma pata",
    vacinas: "Não vacinado",
    alergia: "Não possui",
    foto: "https://s2-g1.glbimg.com/pcKExQnvQlB6_1X1a-k1mMJJQrI=/0x47:960x636/984x0/smart/filters:strip_icc()/i.s3.glbimg.com/v1/AUTH_59edd422c0c84a879bd37670ae4f538a/internal_photos/bs/2019/U/Y/9m3nBqQfCaR6mlquzz3A/whatsapp-image-2019-12-28-at-13.33.28.jpeg",
    whatsapp: "5511777777777",
    ong: "",
  },

  {
    id: 4,
    nome: "Nina",
    tipo: "Gato",
    idade: "4 anos",
    sexo: "Fêmea",
    deficiencia: "Cega de um olho",
    vacinas: "V4",
    alergia: "Peixe",
    foto: "https://www.zooplus.pt/magazine/wp-content/uploads/2021/06/gato-cego-sofa-768x512-1.webp",
    whatsapp: "5511666666666",
    ong: "",
  },

  {
    id: 5,
    nome: "Maya",
    tipo: "Cachorro",
    idade: "5 anos",
    sexo: "Fêmea",
    deficiencia: "Perda parcial da visão",
    vacinas: "Completa",
    alergia: "Não possui",
    foto: "https://www.olhoclinico.com.br/wp-content/uploads/2019/06/cataracts-dogs.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 6,
    nome: "Magnus",
    tipo: "Cachorro",
    idade: "6 anos",
    sexo: "Macho",
    deficiencia: "Não possui",
    vacinas: "Completa",
    alergia: "Carne suina",
    foto: "https://fisiocarepet.com.br/wp-content/uploads/2021/12/pastor-1.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 7,
    nome: "Bento",
    tipo: "Cachorro",
    idade: "2 anos",
    sexo: "Macho",
    deficiencia: "Não possui",
    vacinas: "Não vacinado",
    alergia: "Não possui",
    foto: "https://www.portaldodog.com.br/wp-content/uploads/2025/03/Nova-raca-de-cachorro.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 8,
    nome: "Linda",
    tipo: "Gato",
    idade: "4 anos",
    sexo: "Fêmea",
    deficiencia: "Não possui",
    vacinas: "Incompleta",
    alergia: "Não possui",
    foto: "https://chemitec.com.br/wp-content/uploads/2025/04/gatos-mais-populares-300x200.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 9,
    nome: "Marley",
    tipo: "Cachorro",
    idade: "7 anos",
    sexo: "Macho",
    deficiencia: "Surdez parcial",
    vacinas: "Completa",
    alergia: "Não possui",
    foto: "https://blogs.correiobraziliense.com.br/wp-content/uploads/mais-bichos/b135b2fc0494714710d961e205c00d45.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 10,
    nome: "Tom",
    tipo: "Gato",
    idade: "3 anos",
    sexo: "Macho",
    deficiencia: "Não possui",
    vacinas: "completa",
    alergia: "Frango",
    foto: "https://admin.cnnbrasil.com.br/wp-content/uploads/sites/12/2025/05/gato-laranja-e1748043537291.jpg?w=1200&h=630&crop=1",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 11,
    nome: "Mimi",
    tipo: "Cachorro",
    idade: "4 anos",
    sexo: "Fêmea",
    deficiencia: "Perdeu uma pata traseira",
    vacinas: "Incompleta",
    alergia: "Não possui",
    foto: "https://s2.glbimg.com/M_Fx0ndML7Z-kNaykmM8iX9Tvlw=/s.glbimg.com/jo/g1/f/original/2014/09/08/cao3.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 12,
    nome: "Frederico",
    tipo: "Cachorro",
    idade: "3 anos",
    sexo: "Macho",
    deficiencia: "Não possui",
    vacinas: "Completa",
    alergia: "Shampoos específicos",
    foto: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTMfoGzR2puTWIXIHld6TfzSW7aM8qGe2h-TQ&s",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 13,
    nome: "Lisa",
    tipo: "Cachorro",
    idade: "1 ano",
    sexo: "Fêmea",
    deficiencia: "Perdeu uma pata da frente",
    vacinas: "V10",
    alergia: "Não possui",
    foto: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRmhd6KLCJ08M99ZTm5-WMjhFlpOq8foXuBWw&s",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 14,
    nome: "Spike",
    tipo: "Cachorro",
    idade: "9 anos",
    sexo: "Macho",
    deficiencia: "Surdez",
    vacinas: "V10",
    alergia: "Não possui",
    foto: "https://revistaanamaria.com.br/wp-content/uploads/2025/03/raca-cachorro-mais-facil-de-cuidar.jpg",
    whatsapp: "5511777777777",
    ong: "",
  },
  {
    id: 15,
    nome: "Bob",
    tipo: "Cachorro",
    idade: "6 anos",
    sexo: "Macho",
    deficiencia: "Problema de visão",
    vacinas: "V10",
    alergia: "Não possui",
    foto: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQEQeFxQpYjMfdsZnfYJVDulwmW8cBRtrNSYg&s",
    whatsapp: "5511777777777",
    ong: "",
  },
]

let animais = [...animaisBase]

async function parseJsonResponse(r) {
  const textRaw = await r.text()
  const text = textRaw.replace(/^\uFEFF/, "").trim()
  if (!r.ok) {
    try {
      const j = JSON.parse(text)
      if (j && (j.error || j.message)) {
        return { ok: false, error: j.error || j.message, httpStatus: r.status, ...j }
      }
    } catch (e) {
      /* resposta não JSON */
    }
    return {
      ok: false,
      error: `Falha HTTP ${r.status}. Verifique se o PHP está ativo e a URL da API está correta.`,
      httpStatus: r.status,
      _bodyPreview: text.slice(0, 200),
    }
  }
  try {
    return JSON.parse(text)
  } catch (e) {
    console.error("Resposta não é JSON do servidor:", text.slice(0, 400))
    return {
      ok: false,
      error: "Erro no servidor (resposta inválida). Abra F12 → Console e recarregue.",
    }
  }
}

async function apiGetBootstrap() {
  const r = await fetch(API_URL + "?action=bootstrap", { credentials: "same-origin" })
  return parseJsonResponse(r)
}

async function apiPost(body) {
  const r = await fetch(API_URL, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify(body),
  })
  return parseJsonResponse(r)
}

function applyBootstrap(data) {
  if (!data || !data.ok) return
  usuarioPerfil = data.user || null
  usuarioLogado = data.user ? data.user.email : null
  appMeta = data.meta || null
  myRating = data.my_rating || null
  favoritos = data.favoritos || []
  adotados = data.adotados || []
  ongs = data.ongs || []
  denunciasLista = data.denuncias || []
  if (data.animais && data.animais.length > 0) {
    animais = data.animais
  } else {
    animais = [...animaisBase, ...(data.animaisExtras || [])]
  }
}

function senhaForteOk(s) {
  if (!s || s.length < 8) return false
  return /[a-z]/.test(s) && /[A-Z]/.test(s) && /[0-9]/.test(s)
}

function ocultarSplash() {
  document.body.classList.remove("com-splash")
  const sp = document.getElementById("splash")
  if (sp) sp.classList.add("splash-hidden")
}

function atualizarTextosPoliticaSenha() {
  const texto = (appMeta && appMeta.password_policy) || ""
  const a = document.getElementById("politicaSenhaAcesso")
  const b = document.getElementById("politicaSenhaTexto")
  if (a) a.textContent = texto
  if (b) b.textContent = texto
}

function preencherFormularioPerfil() {
  if (!usuarioPerfil) return
  const n = document.getElementById("perfilNome")
  const t = document.getElementById("perfilTel")
  if (n) n.value = (usuarioPerfil.display_name || "").trim() || ""
  if (t) t.value = (usuarioPerfil.telefone || "").trim() || ""

  const na = document.getElementById("notifApp")
  const ne = document.getElementById("notifEmail")
  const nw = document.getElementById("notifWa")
  const fq = document.getElementById("notifFreq")
  if (na) na.checked = Number(usuarioPerfil.notif_app) === 1
  if (ne) ne.checked = Number(usuarioPerfil.notif_email) === 1
  if (nw) nw.checked = Number(usuarioPerfil.notif_whatsapp) === 1
  if (fq && usuarioPerfil.notif_freq) fq.value = usuarioPerfil.notif_freq

  const av = document.getElementById("avisoSenhaGoogle")
  if (av) av.hidden = Boolean(usuarioPerfil.has_password)

  const cpfEl = document.getElementById("cpfUsuario")
  if (cpfEl) {
    const m = usuarioPerfil.cpf_masked
    cpfEl.textContent = m ? "CPF: " + m + " (não editável)" : ""
  }
}

function aplicarFundoPerfilUi() {
  const inner = document.getElementById("perfilFundoInner")
  if (!inner) return
  const url = usuarioPerfil && usuarioPerfil.foto_fundo_data
  if (url) {
    inner.style.backgroundImage = "url(" + JSON.stringify(url) + ")"
  } else {
    inner.style.backgroundImage = ""
  }
}

function aplicarRatingUi() {
  const r = myRating
  const stars = r && r.stars ? r.stars : 0
  ratingSelecionado = stars
  const c = document.getElementById("ratingComentario")
  if (c && r && r.comment) c.value = r.comment
  document.querySelectorAll("#ratingStars button").forEach((btn) => {
    const n = Number(btn.getAttribute("data-star"))
    btn.classList.toggle("is-on", n <= stars)
  })
  const rv = document.getElementById("ratingValue")
  if (rv) rv.value = String(stars)
}

function atualizarSobreSistema() {
  const el = document.getElementById("sobreLinha1")
  if (!el || !appMeta) return
  el.textContent =
    (appMeta.app_name || "Vida de Quatro Patas") +
    " — versão " +
    (appMeta.app_version || "—") +
    "."
}

function sincronizarNavAutenticacao() {
  const bEnt = document.getElementById("navBtnEntrar")
  const bConta = document.getElementById("navBtnConta")
  if (!bEnt || !bConta) return
  if (usuarioLogado) {
    bEnt.hidden = true
    bConta.hidden = false
  } else {
    bEnt.hidden = false
    bConta.hidden = true
  }
}

function sincronizarUiAposEstado() {
  atualizarTextosPoliticaSenha()
  atualizarSobreSistema()
  sincronizarNavAutenticacao()

  const logged = document.getElementById("perfilLogado")
  const nomeEl = document.getElementById("nomeUsuario")
  const emailEl = document.getElementById("emailUsuario")

  if (usuarioLogado && usuarioPerfil) {
    if (logged) logged.hidden = false
    const rotulo = usuarioPerfil.display_name || usuarioLogado
    if (nomeEl) nomeEl.innerText = rotulo
    if (emailEl) emailEl.innerText = usuarioLogado
    preencherFormularioPerfil()
    aplicarFundoPerfilUi()
    aplicarRatingUi()
  } else {
    if (logged) logged.hidden = true
    if (nomeEl) nomeEl.innerText = "—"
    if (emailEl) emailEl.innerText = ""
  }

  aplicarFotoPerfil()
  atualizarDashboard()
  renderizarAnimais()
  mostrarFavoritos()
  renderizarAnimaisAdotados()
  mostrarONGs()
  preencherSelectOng()
  renderizarListaDenuncias()
}

/** Remove da lista de favoritos os IDs que já foram adotados (mantém dados consistentes) */
async function sincronizarFavoritosComAdotados() {
  const filtrado = favoritos.filter((id) => !adotados.includes(id))
  if (filtrado.length !== favoritos.length) {
    favoritos = filtrado
    await salvarFavoritosNoServidor()
  }
}

async function salvarFavoritosNoServidor() {
  if (!usuarioLogado) return
  const data = await apiPost({ action: "set_favorites", ids: favoritos })
  if (data.ok) applyBootstrap(data)
  else alert(data.error || "Não foi possível salvar favoritos.")
}

/**
 * Tela "Entrar" (acesso) só é usada com usuário fora. Com sessão, vai à Conta.
 * Tela "Conta" (perfil) exige login; fora, redireciona a Entrar.
 */
function mostrarTela(id) {
  ocultarSplash()
  if (id === "acesso" && usuarioLogado) {
    mostrarTelaDireto("perfil")
    return
  }
  if (id === "perfil" && !usuarioLogado) {
    mostrarTelaDireto("acesso")
    return
  }
  mostrarTelaDireto(id)
}

function mostrarTelaDireto(id) {
  let telas = document.querySelectorAll(".tela")
  telas.forEach((t) => (t.style.display = "none"))

  const tela = document.getElementById(id)
  if (tela) {
    tela.style.display = "block"
  }

  if (id === "animais-adotados") {
    renderizarAnimaisAdotados()
  }
}

function perfilTab(name) {
  document.querySelectorAll(".perfil-tab").forEach((b) => {
    b.classList.toggle("is-active", b.getAttribute("data-tab") === name)
  })
  document.querySelectorAll(".perfil-panel").forEach((p) => {
    p.classList.toggle("is-visible", p.id === "panel-" + name)
  })
}

function obterListaAtual() {
  const busca = document.getElementById("buscaAnimal")
  const categoria = document.getElementById("categoria")

  /* Apenas animais ainda não adotados aparecem na listagem principal */
  let lista = animais.filter((a) => !adotados.includes(a.id))

  if (categoria && categoria.value !== "todos") {
    lista = lista.filter((a) => a.tipo === categoria.value)
  }

  if (busca && busca.value.trim() !== "") {
    const texto = busca.value.toLowerCase()
    lista = lista.filter((a) => a.nome.toLowerCase().includes(texto))
  }

  return lista
}

function animalEhCadastroUsuario(id) {
  const n = Number(id)
  return !Number.isNaN(n) && !IDS_ANIMAIS_PADRAO.has(n)
}

async function removerAnimalCadastro(id) {
  if (!usuarioLogado) {
    alert("Faça login para remover um cadastro.")
    mostrarTela("perfil")
    return
  }
  if (!animalEhCadastroUsuario(id)) {
    alert("Animais da lista principal do site não podem ser removidos.")
    return
  }
  const animal = animais.find((a) => a.id === id)
  const nome = animal ? animal.nome : "Este animal"
  if (
    !confirm(
      `Remover "${nome}" do site? Isso apaga o cadastro, favoritos e adoção vinculados a este animal.`
    )
  ) {
    return
  }
  const data = await apiPost({ action: "remove_animal", animal_id: id })
  if (!data.ok) {
    alert(data.error || "Não foi possível remover o cadastro.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
  alert(`O cadastro de ${nome} foi removido.`)
}

function renderizarAnimais() {
  mostrarAnimais(obterListaAtual())
}

function mostrarAnimais(lista = animais) {
  let area = document.getElementById("listaAnimais")
  if (!area) return

  area.innerHTML = ""

  if (lista.length === 0) {
    area.innerHTML = `<p class="vazio">Nenhum animal disponível no momento. Todos foram adotados ou ainda não há cadastros.</p>`
    return
  }

  lista.forEach((animal) => {
    area.innerHTML += `
      <div class="card animal-card">

        <img src="${animal.foto}" alt="${animal.nome}">

        <div class="animal-topo">
          <h3>${animal.nome}</h3>
          <span class="coracao" onclick="favoritar(${animal.id})">
            ${favoritos.includes(animal.id) ? "💖" : "🤍"}
          </span>
        </div>

        <div class="animal-tags">
          <span class="tag">${animal.tipo}</span>
          <span class="tag tag-suave">${animal.idade}</span>
          <span class="tag tag-suave">${animal.sexo || "—"}</span>
        </div>

        <p><strong>Deficiência:</strong> ${animal.deficiencia}</p>
        <p><strong>Alergia:</strong> ${animal.alergia || "Não possui"}</p>
        <p><strong>Vacinas:</strong> ${animal.vacinas}</p>

        ${animal.ong ? `<p><strong>ONG:</strong> ${animal.ong}</p>` : ""}

        <button onclick="adotar(${animal.id})">
          Adotar
        </button>

        ${
          animalEhCadastroUsuario(animal.id)
            ? `<button type="button" class="btn-remover-animal" onclick="removerAnimalCadastro(${animal.id})">Remover cadastro</button>`
            : ""
        }

      </div>
    `
  })
}

function filtrarAnimais() {
  renderizarAnimais()
}

function buscarAnimal() {
  renderizarAnimais()
}

async function favoritar(id) {
  if (!usuarioLogado) {
    alert("Faça login para usar favoritos.")
    mostrarTela("perfil")
    return
  }

  if (adotados.includes(id)) {
    alert("Este animal já foi adotado e não pode ser favoritado.")
    return
  }

  if (favoritos.includes(id)) {
    favoritos = favoritos.filter((f) => f !== id)
    alert("Animal removido dos favoritos")
  } else {
    favoritos.push(id)
    alert("Animal favoritado ❤️")
  }

  await salvarFavoritosNoServidor()

  mostrarFavoritos()
  renderizarAnimais()
  atualizarDashboard()
}

function mostrarFavoritos() {
  let area = document.getElementById("listaFavoritos")
  if (!area) return

  area.innerHTML = ""

  let lista = animais.filter(
    (a) => favoritos.includes(a.id) && !adotados.includes(a.id)
  )

  if (lista.length === 0) {
    area.innerHTML = `<p class="vazio">Você ainda não favoritou nenhum animal.</p>`
    return
  }

  lista.forEach((animal) => {
    area.innerHTML += `

      <div class="card favorito-card">

        <img src="${animal.foto}" alt="${animal.nome}">

        <div class="animal-topo">
          <h3>${animal.nome}</h3>
          <span class="coracao" onclick="favoritar(${animal.id})">💖</span>
        </div>

        <div class="animal-tags">
          <span class="tag">${animal.tipo}</span>
          <span class="tag tag-suave">${animal.idade}</span>
        </div>

        <p><strong>Deficiência:</strong> ${animal.deficiencia}</p>
        <p><strong>Vacinas:</strong> ${animal.vacinas}</p>

        <button onclick="adotar(${animal.id})">Adotar</button>

        <button class="btn-secundario" onclick="favoritar(${animal.id})">
          Desfavoritar
        </button>

        ${
          animalEhCadastroUsuario(animal.id)
            ? `<button type="button" class="btn-remover-animal" onclick="removerAnimalCadastro(${animal.id})">Remover cadastro</button>`
            : ""
        }

      </div>

    `
  })
}

async function adotar(id) {
  if (!usuarioLogado) {
    alert("Faça login primeiro")
    mostrarTela("perfil")
    return
  }

  const animal = animais.find((a) => a.id === id)
  if (!animal) return

  if (adotados.includes(id)) {
    alert("Este animal já foi adotado e não está mais disponível.")
    mostrarTela("animais-adotados")
    return
  }

  let numero = animal.whatsapp || ""

  if (!numero && animal.ong) {
    const ongEncontrada = ongs.find((o) => o.nome === animal.ong)
    if (ongEncontrada) {
      numero = (ongEncontrada.contato || "").replace(/\D/g, "")
    }
  }

  numero = String(numero).replace(/\D/g, "")

  if (!numero) {
    alert("Esse animal ainda não possui WhatsApp cadastrado.")
    return
  }

  const data = await apiPost({ action: "add_adoption", animal_id: id })
  if (!data.ok) {
    alert(data.error || "Não foi possível registrar a adoção.")
    return
  }
  applyBootstrap(data)

  const mensagem = `Olá! Tenho interesse em adotar ${animal.nome} pelo site Vida de Quatro Patas.`
  const link = `https://wa.me/${numero}?text=${encodeURIComponent(mensagem)}`

  window.open(link, "_blank")

  sincronizarUiAposEstado()
}

/** Lista na aba "Animais adotados" + opção de desistir (volta à disponível) */
function renderizarAnimaisAdotados() {
  const area = document.getElementById("listaAnimaisAdotados")
  if (!area) return

  area.innerHTML = ""

  const lista = [...adotados]
    .reverse()
    .map((idAnimal) => animais.find((a) => a.id === idAnimal))
    .filter(Boolean)

  if (lista.length === 0) {
    area.innerHTML = `<p class="vazio">Nenhum animal adotado ainda. Quando você concluir uma adoção, ele aparecerá aqui.</p>`
    return
  }

  lista.forEach((animal) => {
    area.innerHTML += `
      <div class="card animal-card animal-card--adotado">

        <div class="animal-adotado-ribbon" aria-hidden="true">Adotado</div>

        <img src="${animal.foto}" alt="${animal.nome}">

        <div class="animal-topo">
          <h3>${animal.nome}</h3>
        </div>

        <div class="animal-tags">
          <span class="tag tag-adotado">Indisponível</span>
          <span class="tag">${animal.tipo}</span>
          <span class="tag tag-suave">${animal.idade}</span>
        </div>

        <p><strong>Deficiência:</strong> ${animal.deficiencia}</p>
        <p><strong>Vacinas:</strong> ${animal.vacinas}</p>
        ${animal.ong ? `<p><strong>ONG:</strong> ${animal.ong}</p>` : ""}

        <p class="animal-msg-adotado">Se você desistiu do processo de adoção, pode voltar o animal à lista de disponíveis.</p>

        <button type="button" class="btn-indisponivel" disabled aria-disabled="true">
          Indisponível para adoção
        </button>

        <button type="button" class="btn-desistir-adocao" onclick="desistirAdocao(${animal.id})">
          Desistir da adoção
        </button>

      </div>
    `
  })
}

/**
 * Remove o animal da lista de adotados e volta a exibi-lo como disponível.
 * Exige login (mesma regra da adoção).
 */
async function desistirAdocao(id) {
  if (!usuarioLogado) {
    alert("Faça login para registrar a desistência da adoção.")
    mostrarTela("perfil")
    return
  }

  if (!adotados.includes(id)) {
    return
  }

  const animal = animais.find((a) => a.id === id)
  const nome = animal ? animal.nome : "Este animal"

  if (
    !confirm(
      `${nome} voltará a ficar disponível para adoção no site. Deseja confirmar a desistência?`
    )
  ) {
    return
  }

  const data = await apiPost({ action: "remove_adoption", animal_id: id })
  if (!data.ok) {
    alert(data.error || "Não foi possível atualizar o cadastro.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()

  alert(`${nome} está novamente disponível para adoção.`)
}

function atualizarContador() {
  const contador = document.getElementById("contadorAdotados")
  if (contador) {
    contador.innerText = adotados.length
  }
}

/** Atualiza o src da foto: upload no servidor tem prioridade; depois avatar Google; senão padrão. */
function aplicarFotoPerfil() {
  const img = document.getElementById("fotoPerfil")
  if (!img) return

  const manual = usuarioPerfil && usuarioPerfil.foto_perfil_data
  const googleUrl =
    usuarioPerfil && usuarioPerfil.login_provider === "google"
      ? usuarioPerfil.avatar_url
      : ""

  if (manual) {
    img.src = manual
  } else if (googleUrl) {
    img.src = googleUrl
  } else {
    img.src = FOTO_PERFIL_PADRAO
  }
}

async function handleGoogleCredentialResponse(response) {
  const data = await apiPost({
    action: "google_login",
    credential: response.credential,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível concluir o login com Google.")
    return
  }
  applyBootstrap(data)
  if (!usuarioLogado) {
    alert("Sessão não reconhecida após o Google. Recarregue a página (F5).")
    return
  }
  sincronizarUiAposEstado()
  mostrarTela("perfil")
  alert("Login com Google realizado")
}

function initGoogleSignIn() {
  if (!GOOGLE_CLIENT_ID || typeof google === "undefined" || !google.accounts) {
    return
  }

  const container = document.getElementById("googleSignInButton")
  const aviso = document.getElementById("googleSignInAviso")
  if (!container) return

  container.innerHTML = ""

  google.accounts.id.initialize({
    client_id: GOOGLE_CLIENT_ID,
    callback: handleGoogleCredentialResponse,
    ux_mode: "popup",
  })

  google.accounts.id.renderButton(container, {
    theme: "outline",
    size: "large",
    width: "100%",
    text: "signin_with",
    locale: "pt_BR",
  })

  if (aviso) aviso.hidden = true
}

function tentarInitGoogleSignIn() {
  if (!GOOGLE_CLIENT_ID) return
  if (typeof google !== "undefined" && google.accounts && google.accounts.id) {
    initGoogleSignIn()
    return
  }
  setTimeout(tentarInitGoogleSignIn, 80)
}

/**
 * Enter no formulário = Entrar (mesma ação do botão Entrar).
 */
async function acessoOnSubmit(e) {
  e.preventDefault()
  await login()
  return false
}

async function registrarUsuario() {
  const emailInput = document.getElementById("email")
  const senhaInput = document.getElementById("senha")
  if (!emailInput || !senhaInput) {
    alert('Campos não encontrados. Abra a tela "Entrar" (menu) e tente de novo.')
    return
  }
  let email = emailInput.value.trim()
  let senha = senhaInput.value.trim()

  if (email === "" || senha === "") {
    alert("Preencha e-mail e senha para criar a conta.")
    return
  }
  if (!senhaForteOk(senha)) {
    alert(
      (appMeta && appMeta.password_policy) ||
        "A senha deve ter no mínimo 8 caracteres, com maiúscula, minúscula e número."
    )
    return
  }

  const data = await apiPost({
    action: "register",
    email,
    password: senha,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível cadastrar.")
    return
  }
  applyBootstrap(data)
  if (!usuarioLogado) {
    alert("Conta não foi associada à sessão. Recarregue a página (F5) e tente de novo.")
    return
  }
  senhaInput.value = ""
  sincronizarUiAposEstado()
  mostrarTela("perfil")
  alert("Conta criada. Você já está logado.")
}

async function login() {
  const emailInput = document.getElementById("email")
  const senhaInput = document.getElementById("senha")
  if (!emailInput || !senhaInput) {
    alert('Campos de login não encontrados. Use o menu "Entrar" e recarregue a página (F5) se o problema continuar.')
    return
  }
  let email = emailInput.value.trim()
  let senha = senhaInput.value.trim()

  if (email === "" || senha === "") {
    alert("Preencha e-mail e senha.")
    return
  }

  const data = await apiPost({
    action: "login",
    email: email,
    password: senha,
  })
  if (!data.ok) {
    if (data.code === "EMAIL_NOT_FOUND") {
      if (senhaForteOk(senha)) {
        const criar = confirm(
          "Não existe conta com este e-mail.\n\nDeseja CRIAR a conta agora com o e-mail e a senha que você preencheu?"
        )
        if (criar) {
          await registrarUsuario()
        }
        return
      }
      alert(
        "Este e-mail ainda não está cadastrado.\n\n" +
          "Defina uma senha com 8+ caracteres, maiúscula, minúscula e número (ex.: MinhaSenh4) e clique no botão «Criar minha conta»."
      )
      return
    }
    if (data.code === "GOOGLE_ONLY") {
      alert(data.error)
      return
    }
    alert(
      data.error ||
        (data.httpStatus
          ? "Falha na comunicação com o servidor (HTTP " + data.httpStatus + ")."
          : "Falha no login.")
    )
    if (data._bodyPreview) {
      console.error("Resposta do servidor (trecho):", data._bodyPreview)
    }
    return
  }
  applyBootstrap(data)
  if (!usuarioLogado) {
    alert("Resposta inválida: sessão não foi reconhecida. Abra o Console (F12) e recarregue.")
    return
  }
  senhaInput.value = ""
  sincronizarUiAposEstado()
  mostrarTela("perfil")
  alert("Login realizado. Bem-vindo(a)!")
}

async function logout() {
  try {
    const data = await apiPost({ action: "logout" })
    applyBootstrap(data)
  } catch (e) {
    console.error(e)
  }
  const em = document.getElementById("email")
  const sn = document.getElementById("senha")
  if (em) em.value = ""
  if (sn) sn.value = ""
  sincronizarUiAposEstado()
  mostrarTelaDireto("acesso")

  if (typeof google !== "undefined" && google.accounts && google.accounts.id) {
    google.accounts.id.disableAutoSelect()
  }

  alert("Você saiu da conta.")
}

async function salvarPerfilConta() {
  if (!usuarioLogado) return
  const display_name = document.getElementById("perfilNome").value.trim()
  const telefone = document.getElementById("perfilTel").value.trim()
  if (!display_name) {
    alert("Informe um nome de exibição.")
    return
  }
  const data = await apiPost({ action: "update_profile", display_name, telefone })
  if (!data.ok) {
    alert(data.error || "Não foi possível salvar.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
  alert("Dados salvos.")
}

async function salvarNotificacoes() {
  if (!usuarioLogado) return
  const data = await apiPost({
    action: "update_notifications",
    notif_app: document.getElementById("notifApp").checked,
    notif_email: document.getElementById("notifEmail").checked,
    notif_whatsapp: document.getElementById("notifWa").checked,
    notif_freq: document.getElementById("notifFreq").value,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível salvar.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
  alert("Preferências salvas.")
}

async function trocarFundoPerfil(event) {
  if (!usuarioLogado) {
    alert("Faça login para alterar o plano de fundo.")
    event.target.value = ""
    return
  }
  const arquivo = event.target.files[0]
  if (!arquivo) return
  const leitor = new FileReader()
  leitor.onload = async function () {
    const data = await apiPost({
      action: "set_background_photo",
      foto_data_url: leitor.result,
    })
    if (!data.ok) {
      alert(data.error || "Não foi possível salvar o fundo.")
      return
    }
    applyBootstrap(data)
    sincronizarUiAposEstado()
  }
  leitor.readAsDataURL(arquivo)
}

async function limparFundoPerfil() {
  if (!usuarioLogado) return
  const data = await apiPost({ action: "clear_background_photo" })
  if (!data.ok) {
    alert(data.error || "Erro ao remover.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
}

async function limparFotoPerfil() {
  if (!usuarioLogado) return
  if (!confirm("Remover a foto de perfil enviada?")) return
  const data = await apiPost({ action: "clear_profile_photo" })
  if (!data.ok) {
    alert(data.error || "Erro ao remover.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
}

async function trocarSenhaConta() {
  if (!usuarioLogado) return
  const cur = document.getElementById("senhaAtual").value
  const neu = document.getElementById("senhaNova").value
  if (!senhaForteOk(neu)) {
    alert(
      (appMeta && appMeta.password_policy) ||
        "A nova senha não atende à política de segurança."
    )
    return
  }
  const data = await apiPost({
    action: "change_password",
    current_password: cur,
    new_password: neu,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível alterar a senha.")
    return
  }
  document.getElementById("senhaAtual").value = ""
  document.getElementById("senhaNova").value = ""
  applyBootstrap(data)
  sincronizarUiAposEstado()
  alert("Senha alterada.")
}

async function solicitarRecuperacaoSenha() {
  const email = document.getElementById("recEmail").value.trim()
  const msg = document.getElementById("recMsg")
  const extra = document.getElementById("recExtra")
  if (!email) {
    if (msg) msg.textContent = "Informe o e-mail cadastrado."
    return
  }
  const data = await apiPost({ action: "forgot_password", email })
  if (!data.ok) {
    if (msg) msg.textContent = data.error || "Erro."
    return
  }
  if (msg) msg.textContent = data.message || ""
  if (extra) extra.hidden = false
  if (data.reset_token) {
    const t = document.getElementById("recToken")
    if (t) t.value = data.reset_token
  }
}

async function redefinirSenhaToken() {
  const email = document.getElementById("recEmail").value.trim()
  const token = document.getElementById("recToken").value.trim()
  const new_password = document.getElementById("recNovaSenha").value
  const msg = document.getElementById("recMsg")
  if (!senhaForteOk(new_password)) {
    const t =
      (appMeta && appMeta.password_policy) ||
      "A nova senha não atende à política de segurança."
    if (msg) msg.textContent = t
    alert(t)
    return
  }
  const data = await apiPost({ action: "reset_password", email, token, new_password })
  if (!data.ok) {
    if (msg) msg.textContent = data.error || "Erro."
    alert(data.error || "Não foi possível redefinir.")
    return
  }
  if (msg) msg.textContent = data.message || "Senha redefinida."
  alert(data.message || "Senha redefinida. Você já pode entrar.")
}

async function iniciarExclusaoConta() {
  const wrap = document.getElementById("exclusaoWrap")
  const msg = document.getElementById("exclusaoMsg")
  if (wrap) wrap.hidden = false
  const data = await apiPost({ action: "request_account_deletion" })
  if (!data.ok) {
    if (msg) msg.textContent = data.error || "Erro."
    return
  }
  if (msg) msg.textContent = data.message || ""
  if (data.deletion_token) {
    const t = document.getElementById("tokenExclusao")
    if (t) t.value = data.deletion_token
  }
}

async function confirmarExclusaoConta() {
  const token = document.getElementById("tokenExclusao").value.trim()
  const password = document.getElementById("senhaExclusao").value
  const msg = document.getElementById("exclusaoMsg")
  if (!token) {
    alert("Informe o código de confirmação.")
    return
  }
  if (!confirm("Excluir permanentemente sua conta e dados vinculados neste servidor?")) {
    return
  }
  const data = await apiPost({
    action: "confirm_account_deletion",
    deletion_token: token,
    password: password,
  })
  if (!data.ok) {
    if (msg) msg.textContent = data.error || "Erro."
    alert(data.error || "Não foi possível excluir.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
  mostrarTelaDireto("acesso")
  alert("Conta excluída.")
}

function setRatingStar(n) {
  ratingSelecionado = n
  const rv = document.getElementById("ratingValue")
  if (rv) rv.value = String(n)
  document.querySelectorAll("#ratingStars button").forEach((btn) => {
    const sn = Number(btn.getAttribute("data-star"))
    btn.classList.toggle("is-on", sn <= n)
  })
}

async function enviarAvaliacao() {
  if (!usuarioLogado) {
    alert("Faça login para avaliar.")
    return
  }
  const stars =
    ratingSelecionado || Number(document.getElementById("ratingValue").value || 0)
  const comment = document.getElementById("ratingComentario").value.trim()
  const st = document.getElementById("ratingStatus")
  if (stars < 1 || stars > 5) {
    if (st) st.textContent = "Selecione de 1 a 5 estrelas."
    return
  }
  const data = await apiPost({ action: "submit_rating", stars, comment })
  if (!data.ok) {
    if (st) st.textContent = data.error || "Erro."
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()
  if (st) st.textContent = "Obrigado pela avaliação!"
}

/** Texto de endereço para cards (compatível com ONGs antigas só com cidade). */
function blocoLocalOngHtml(o) {
  const r = (o.rua || "").trim()
  const n = (o.numero || "").trim()
  const b = (o.bairro || "").trim()
  const c = (o.cidade || "").trim()
  const uf = (o.estado || "").trim()

  const temEnderecoNovo = !!(r || n || b || uf)
  if (temEnderecoNovo) {
    const partes = []
    if (r || n) {
      partes.push([r, n ? `nº ${n}` : ""].filter(Boolean).join(", "))
    }
    if (b) partes.push(b)
    if (c || uf) partes.push([c, uf].filter(Boolean).join(" — "))
    return `<p><strong>Endereço:</strong> ${partes.join(" — ")}</p>`
  }
  if (c) {
    return `<p><strong>Cidade:</strong> ${c}</p>`
  }
  return ""
}

async function cadastrarONG() {
  let nome = document.getElementById("nomeOng").value.trim()
  let rua = document.getElementById("ruaOng").value.trim()
  let numero = document.getElementById("numeroOng").value.trim()
  let bairro = document.getElementById("bairroOng").value.trim()
  let cidade = document.getElementById("cidadeOng").value.trim()
  let estado = document.getElementById("estadoOng").value.trim()
  let contato = document.getElementById("contatoOng").value.trim()

  if (!nome || !rua || !numero || !bairro || !cidade || !estado || !contato) {
    alert("Preencha todos os campos da ONG (nome, endereço completo e contato).")
    return
  }

  const data = await apiPost({
    action: "add_ong",
    nome,
    rua,
    numero,
    bairro,
    cidade,
    estado,
    contato,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível cadastrar a ONG.")
    return
  }
  applyBootstrap(data)

  document.getElementById("nomeOng").value = ""
  document.getElementById("ruaOng").value = ""
  document.getElementById("numeroOng").value = ""
  document.getElementById("bairroOng").value = ""
  document.getElementById("cidadeOng").value = ""
  document.getElementById("estadoOng").value = ""
  document.getElementById("contatoOng").value = ""

  sincronizarUiAposEstado()

  mostrarTela("registrar-animais")
  preencherSelectOng()
  const sel = document.getElementById("ongAnimal")
  if (sel) sel.value = nome

  alert("ONG cadastrada com sucesso! Agora você pode registrar os animais desta ONG.")
}

async function removerOng(id) {
  const o = ongs.find((x) => x.id === id)
  if (!o) return
  if (!confirm(`Remover a ONG "${o.nome}" (${o.cidade})? Esta ação não pode ser desfeita.`)) {
    return
  }

  const data = await apiPost({ action: "remove_ong", id })
  if (!data.ok) {
    alert(data.error || "Não foi possível remover a ONG.")
    return
  }
  applyBootstrap(data)
  sincronizarUiAposEstado()

  const busca = document.getElementById("buscarCidade")
  if (busca && busca.value.trim() !== "") {
    buscarOngCidade()
  }
}

function mostrarONGs() {
  let area = document.getElementById("listaOngs")
  if (!area) return

  area.innerHTML = ""

  if (ongs.length === 0) {
    area.innerHTML = `<p class="vazio">Nenhuma ONG cadastrada ainda.</p>`
    return
  }

  ongs.forEach((o) => {
    if (o.id == null) return
    const localHtml = blocoLocalOngHtml(o)
    area.innerHTML += `
      <div class="card ong-card">
        <h3>${o.nome}</h3>
        ${localHtml}
        <p><strong>Contato:</strong> ${o.contato}</p>
        <div class="ong-card-acoes">
          <button type="button" class="btn-ong-remover" onclick="removerOng(${o.id})">Remover ONG</button>
        </div>
      </div>
    `
  })
}

function atualizarDashboard() {
  const adotadosEl = document.getElementById("contadorAdotados")
  const favoritosEl = document.getElementById("contadorFavoritos")
  const ongsEl = document.getElementById("contadorOngs")

  if (adotadosEl) adotadosEl.innerText = adotados.length
  if (favoritosEl) favoritosEl.innerText = favoritos.length
  if (ongsEl) ongsEl.innerText = ongs.length
}

function preencherSelectOng() {
  const select = document.getElementById("ongAnimal")
  if (!select) return

  const valorAtual = select.value

  select.innerHTML = `<option value="">Sem ONG vinculada</option>`

  ongs.forEach((ong) => {
    select.innerHTML += `<option value="${ong.nome}">${ong.nome}</option>`
  })

  select.value = valorAtual
}

async function cadastrarAnimal() {
  let nome = document.getElementById("nomeAnimal").value.trim()
  let tipo = document.getElementById("tipoAnimal").value
  let idade = document.getElementById("idadeAnimal").value.trim()
  let sexoEl = document.getElementById("sexoAnimal")
  let sexo = sexoEl ? sexoEl.value : "Não informado"
  let vacinas = document.getElementById("vacinaAnimal").value.trim()
  let deficiencia = document.getElementById("deficienciaAnimal").value.trim()
  let alergia = document.getElementById("alergiaAnimal")
    ? document.getElementById("alergiaAnimal").value.trim()
    : ""
  let foto = document.getElementById("fotoAnimal").value.trim()
  let whatsapp = document.getElementById("whatsAnimal").value.trim()
  let ong = document.getElementById("ongAnimal").value

  if (!nome || !idade || !vacinas || !foto) {
    alert("Preencha os campos obrigatórios do animal")
    return
  }

  const data = await apiPost({
    action: "add_animal",
    nome,
    tipo,
    idade,
    sexo,
    vacinas,
    deficiencia: deficiencia || "Nenhuma",
    alergia: alergia || "Não possui",
    foto,
    whatsapp,
    ong,
  })
  if (!data.ok) {
    alert(data.error || "Não foi possível cadastrar o animal.")
    return
  }
  applyBootstrap(data)

  document.getElementById("nomeAnimal").value = ""
  document.getElementById("idadeAnimal").value = ""
  document.getElementById("vacinaAnimal").value = ""
  document.getElementById("deficienciaAnimal").value = ""
  if (document.getElementById("alergiaAnimal")) document.getElementById("alergiaAnimal").value = ""
  document.getElementById("fotoAnimal").value = ""
  document.getElementById("whatsAnimal").value = ""
  document.getElementById("ongAnimal").value = ""

  sincronizarUiAposEstado()
  alert("Animal cadastrado!")
}

function trocarFoto(event) {
  if (!usuarioLogado) {
    alert("Faça login para alterar a foto do perfil.")
    event.target.value = ""
    return
  }

  const arquivo = event.target.files[0]
  if (!arquivo) return

  const leitor = new FileReader()

  leitor.onload = async function () {
    const dataUrl = leitor.result
    const data = await apiPost({
      action: "set_profile_photo",
      foto_data_url: dataUrl,
    })
    if (!data.ok) {
      alert(data.error || "Não foi possível salvar a foto.")
      return
    }
    applyBootstrap(data)
    sincronizarUiAposEstado()
  }

  leitor.readAsDataURL(arquivo)
}

function buscarOngCidade() {
  let cidade = document.getElementById("buscarCidade").value.toLowerCase()
  let resultado = ongs.filter((o) => o.cidade.toLowerCase().includes(cidade))

  let area = document.getElementById("resultadoBusca")
  area.innerHTML = ""

  if (resultado.length === 0) {
    area.innerHTML = `<p class="vazio">Nenhuma ONG encontrada nessa cidade.</p>`
    return
  }

  resultado.forEach((o) => {
    if (o.id == null) return
    const localHtml = blocoLocalOngHtml(o)
    area.innerHTML += `
      <div class="card ong-card">
        <h3>${o.nome}</h3>
        ${localHtml}
        <p><strong>Contato:</strong> ${o.contato}</p>
        <div class="ong-card-acoes">
          <button type="button" class="btn-ong-remover" onclick="removerOng(${o.id})">Remover ONG</button>
        </div>
      </div>
    `
  })
}

function obterDenuncias() {
  return denunciasLista || []
}

/** Evita quebra de HTML ao exibir textos salvos */
function escapeHtml(str) {
  if (str == null) return ""
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/"/g, "&quot;")
}

/** Monta o painel de denúncias na lateral */
function renderizarListaDenuncias() {
  const area = document.getElementById("listaDenuncias")
  if (!area) return

  const lista = obterDenuncias()
  area.innerHTML = ""

  if (lista.length === 0) {
    area.innerHTML = `<p class="vazio">Nenhuma denúncia registrada ainda.</p>`
    return
  }

  lista.forEach((d) => {
    let imgHtml = ""
    if (d.fotoDataUrl) {
      imgHtml = `<img class="denuncia-thumb" src="${d.fotoDataUrl}" alt="">`
    } else if (d.fotoUrl) {
      imgHtml = `<img class="denuncia-thumb" src="${d.fotoUrl}" alt="">`
    }
    const desc = (d.descricao || "").slice(0, 200)
    const descShort = desc + ((d.descricao || "").length > 200 ? "…" : "")
    area.innerHTML += `
      <div class="denuncia-item">
        <strong>${escapeHtml(d.tipo)}</strong> — ${escapeHtml(d.cidade)}
        <p style="margin:8px 0 0;color:var(--texto-suave);">${escapeHtml(descShort)}</p>
        <div class="denuncia-item-meta">
          ${d.denunciante ? "Por: " + escapeHtml(d.denunciante) + " · " : ""}
          ${escapeHtml(d.dataLabel || "")}
        </div>
        ${imgHtml}
      </div>
    `
  })
}

function enviarDenuncia(event) {
  event.preventDefault()

  const denunciante = document.getElementById("denuncianteNome").value.trim()
  const cidade = document.getElementById("denunciaCidade").value.trim()
  const endereco = document.getElementById("denunciaEndereco").value.trim()
  const tipo = document.getElementById("denunciaTipo").value
  const descricao = document.getElementById("denunciaDescricao").value.trim()
  const contato = document.getElementById("denunciaContato").value.trim()
  const fotoUrl = document.getElementById("denunciaFotoUrl").value.trim()
  const arquivo = document.getElementById("denunciaFotoArquivo").files[0]
  const msgEl = document.getElementById("denunciaMsg")

  if (!denunciante || !cidade || !endereco || !tipo || !descricao || !contato) {
    if (msgEl) msgEl.textContent = "Preencha todos os campos obrigatórios."
    alert("Preencha todos os campos obrigatórios.")
    return
  }

  const enviarComFoto = async (fotoDataUrl) => {
    const data = await apiPost({
      action: "add_denuncia",
      denunciante,
      cidade,
      endereco,
      tipo,
      descricao,
      contato,
      fotoUrl,
      fotoDataUrl,
    })
    if (!data.ok) {
      if (msgEl) msgEl.textContent = data.error || "Erro ao enviar."
      alert(data.error || "Não foi possível registrar a denúncia.")
      return
    }
    applyBootstrap(data)
    document.getElementById("formDenuncia").reset()
    if (msgEl) msgEl.textContent = "Denúncia registrada com sucesso!"
    renderizarListaDenuncias()
  }

  if (arquivo) {
    const reader = new FileReader()
    reader.onload = function () {
      enviarComFoto(reader.result)
    }
    reader.readAsDataURL(arquivo)
  } else {
    enviarComFoto("")
  }
}

window.onload = async function () {
  if (window.location && window.location.protocol === "file:") {
    alert(
      "Abra o site com PHP ativo, não o arquivo no disco.\n\nEx.: na pasta do projeto, rode no terminal: php -S localhost:8080\ne abra: http://localhost:8080/index.php"
    )
  }

  const avisoGoogle = document.getElementById("googleSignInAviso")
  const wrapGoogle = document.getElementById("googleSignInButton")
  const dividerGoogle = document.getElementById("perfilDividerGoogle")
  if (!GOOGLE_CLIENT_ID) {
    if (wrapGoogle) wrapGoogle.style.display = "none"
    if (dividerGoogle) dividerGoogle.style.display = "none"
  } else if (avisoGoogle) {
    avisoGoogle.hidden = true
  }

  try {
    const data = await apiGetBootstrap()
    applyBootstrap(data)
  } catch (e) {
    alert(
      "Não foi possível carregar os dados do servidor. Inicie o PHP na pasta do projeto (ex.: php -S localhost:8080) e abra index.php no navegador."
    )
    applyBootstrap({
      ok: true,
      meta: {
        app_name: "Vida de Quatro Patas",
        app_version: "—",
        password_policy:
          "A senha deve ter no mínimo 8 caracteres, com ao menos uma letra maiúscula, uma minúscula e um número.",
      },
      user: null,
      my_rating: null,
      favoritos: [],
      adotados: [],
      ongs: [],
      animaisExtras: [],
      denuncias: [],
    })
  }

  await sincronizarFavoritosComAdotados()
  mostrarTelaDireto("home")
  sincronizarUiAposEstado()
  tentarInitGoogleSignIn()

  const sp = document.getElementById("splash")
  if (sp) {
    sp.addEventListener("click", ocultarSplash)
    setTimeout(ocultarSplash, 2600)
  }
  perfilTab("conta")
}

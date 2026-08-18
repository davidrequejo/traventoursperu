window.top == window && window.console && (
    setTimeout(
        console.log.bind(console, "%c%s", "color: red; background: yellow; font-size: 24px;", "ADVERTENCIA")
    ),
    setTimeout(
    console.log.bind(console, "%c%s", "font-size: 18px;", "Si utilizas esta consola, otras personas podr\u00edan hacerse pasar por ti y robarte datos mediante un ataque Self-XSS.\nNo escribas ni pegues ning\u00fan c\u00f3digo que no entiendas.")
    ),
    setTimeout(
        console.log.bind(console, "%c%s", "color: red; background: yellow; font-size: 24px;", "Cualquier acto de manipulaci\u00f3n de c\u00f3digo, sera registrado!!")
    )
);

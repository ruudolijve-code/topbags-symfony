# Style Guide: marktpositionering

De Style Guide behandelt marktpositionering als een eigen domeinconcept. Het is geen objectief kwaliteitscijfer.

De productpositie (0–100) bestaat uit de marktpositionering van het merk, de materiaalmodifier en een lichte prijsmodifier. Een productspecifieke override vervangt die berekening volledig.

De segmenten blijven gedragsmatig gelijk:

- 0–29: budget
- 30–54: value
- 55–79: premium
- 80–100: luxury

`MaterialFamily` groepeert concrete materialen (bijvoorbeeld leer, synthetisch en textiel) zonder de bestaande materiaalmatching te wijzigen. De relatie is optioneel, zodat bestaande materialen na de migratie geldig blijven.

De migratie hernoemt de bestaande kolommen in-place en behoudt daarmee alle ingestelde waarden.

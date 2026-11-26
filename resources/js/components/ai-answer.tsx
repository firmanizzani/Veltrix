export function AnswerText({ content }: { content: string }) {
    function inline(text: string) {
        return text.split(/(\*\*[^*]+\*\*|`[^`]+`|\*[^*]+\*)/g).map((part, index) => {
            if (part.startsWith('**') && part.endsWith('**')) {
                return <strong key={index}>{part.slice(2, -2)}</strong>;
            }
            if (part.startsWith('`') && part.endsWith('`')) {
                return <code key={index}>{part.slice(1, -1)}</code>;
            }
            if (part.startsWith('*') && part.endsWith('*')) {
                return <em key={index}>{part.slice(1, -1)}</em>;
            }
            return part;
        });
    }
    return (
        <div className="intelligence-answer">
            {content.split(/\n\s*\n/).map((paragraph, index) => {
                const lines = paragraph.split('\n');
                if (lines.every((line) => /^\s*[-*]\s/.test(line))) {
                    return (
                        <ul key={index}>
                            {lines.map((line, row) => (
                                <li key={row}>
                                    {inline(line.replace(/^\s*[-*]\s/, ''))}
                                </li>
                            ))}
                        </ul>
                    );
                }
                return <p key={index}>{inline(paragraph)}</p>;
            })}
        </div>
    );
}

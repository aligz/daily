const userColors = [
    'bg-red-500 text-white',
    'bg-orange-500 text-white',
    'bg-amber-500 text-white',
    'bg-yellow-500 text-black',
    'bg-lime-500 text-black',
    'bg-green-500 text-white',
    'bg-emerald-500 text-white',
    'bg-teal-500 text-white',
    'bg-cyan-500 text-black',
    'bg-sky-500 text-white',
    'bg-blue-500 text-white',
    'bg-indigo-500 text-white',
    'bg-violet-500 text-white',
    'bg-purple-500 text-white',
    'bg-fuchsia-500 text-white',
    'bg-pink-500 text-white',
    'bg-rose-500 text-white',
];

export function getUserColor(name: string): string {
    let hash = 0;

    for (let index = 0; index < name.length; index += 1) {
        hash = name.charCodeAt(index) + ((hash << 5) - hash);
    }

    return userColors[Math.abs(hash) % userColors.length];
}

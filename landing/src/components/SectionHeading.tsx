type SectionHeadingProps = {
  eyebrow: string;
  title: string;
  accent: string;
  subtitle: string;
};

export function SectionHeading({ eyebrow, title, accent, subtitle }: SectionHeadingProps) {
  return (
    <div className="mx-auto mb-12 max-w-3xl text-center md:mb-16">
      <span className="pill mx-auto !font-mono !text-[10.5px] !font-medium !tracking-[0.14em] uppercase">{eyebrow}</span>
      <h2 className="mt-5 text-balance text-3xl font-black tracking-normal text-slate-950 md:text-5xl dark:text-white">
        {title} <span className="text-brand">{accent}</span>
      </h2>
      <p className="mx-auto mt-5 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">{subtitle}</p>
    </div>
  );
}
